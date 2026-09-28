<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Kubernetes;

use League\Flysystem\Config;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Visibility;
use SensitiveParameter;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\ScopedClientFactoryInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Exception\BadTempFileException;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Exception\TokenFileNotAllowedException;
use Teknoo\Kubernetes\Client as KubClient;
use Teknoo\Kubernetes\RepositoryRegistry;
use Psr\Http\Client\ClientInterface;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Throwable;

use function array_diff_key;
use function bin2hex;
use function file_exists;
use function random_bytes;

/**
 * Factory in the DI to create, on demand, a new `Kubernetes Client` instance,
 * needed to execute manifest on the remote Kubernetes manager.
 *
 * Certificates and the client's private key are materialized, through the workspace filesystem, into temporary
 * files readable only by the worker's user (`0600`), the Kubernetes client reading them at each connection. They are
 * written into a new private directory (`0700`), with an unpredictable name, dedicated to the client: the
 * LocalFilesystemAdapter applies the visibility of a file after having written it, the directory prevents others
 * users of the worker to read it meanwhile.
 *
 * The factory is a shared service living as long as the worker: `withClient()` removes the directory of the client
 * it lends as soon as the callback has returned or thrown. The directory is removed when a client can not be built,
 * and `__destruct()` removes the directories of clients created with `__invoke()`, as a last resort.
 *
 * The Kubernetes client reads the token from a file when its value is the path of an existing file. The token of a
 * cluster, set by its owner, would allow to send any file of the worker, as bearer token, to the cluster's address:
 * such token is refused unless `$allowTokenFile` is enabled (DI parameter
 * `teknoo.east.paas.kubernetes.token.allow_file`).
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Factory implements ScopedClientFactoryInterface
{
    /**
     * Private directories of the clients, relative to the filesystem
     *
     * @var array<string, string>
     */
    private array $directories = [];

    /**
     * @var callable(): string
     */
    private $directoryNameFactory;

    /**
     * @param FilesystemOperator $filesystem workspace filesystem (rooted at the worker temp dir) used to materialize
     *        the credentials files
     * @param string $tmpDir absolute path of the workspace filesystem root, used to build the absolute paths passed
     *        to the Kubernetes client
     * @param (callable(): string)|null $directoryNameFactory overridable generator of the name of the private
     *        directory, relative to the workspace filesystem, holding the credentials files of a client (defaults to
     *        a random name, it must be unpredictable)
     */
    public function __construct(
        private readonly FilesystemOperator $filesystem,
        private readonly string $tmpDir,
        private readonly ?ClientInterface $httpClient = null,
        private readonly bool $sslVerify = true,
        private readonly ?int $timeout = null,
        ?callable $directoryNameFactory = null,
        private readonly bool $allowTokenFile = false,
    ) {
        if (null !== $directoryNameFactory) {
            $this->directoryNameFactory = $directoryNameFactory;
        } else {
            $this->directoryNameFactory = static fn (): string => 'east-paas-kube-' . bin2hex(random_bytes(16));
        }
    }

    public function __invoke(
        string $master,
        ?ClusterCredentials $credentials,
        ?RepositoryRegistry $repositoryRegistry = null
    ): KubClient {
        $options = [
            'master' => $master,
            'verify' => $this->sslVerify,
        ];

        if (!empty($this->timeout)) {
            $options['timeout'] = $this->timeout;
        }

        //Same check as the Kubernetes client to decide to read the token from a file: it must be exactly the same
        //(absolute path or relative to the working directory), a filesystem abstraction could normalize the path
        //differently and let pass a path read by the client.
        $token = $credentials?->getToken();
        if (!empty($token) && !$this->allowTokenFile && file_exists($token)) {
            throw new TokenFileNotAllowedException(
                'The token of the cluster designates a file of the worker, this is not allowed',
            );
        }

        $directory = null;

        try {
            if (null !== $credentials) {
                if (!empty($content = $credentials->getCaCertificate())) {
                    $options['ca_cert'] = $this->write($directory, 'ca.crt', $content);
                }

                if (!empty($content = $credentials->getClientCertificate())) {
                    $options['client_cert'] = $this->write($directory, 'client.crt', $content);
                }

                if (!empty($content = $credentials->getClientKey())) {
                    $options['client_key'] = $this->write($directory, 'client.key', $content);
                }

                if (!empty($token)) {
                    $options['token'] = $token;
                }

                if (!empty($content = $credentials->getUsername())) {
                    $options['username'] = $content;
                }

                if (!empty($content = $credentials->getPassword())) {
                    $options['password'] = $content;
                }
            }

            return new KubClient(
                $options,
                $repositoryRegistry,
                $this->httpClient,
            );
        } catch (Throwable $error) {
            if (null !== $directory) {
                try {
                    $this->delete([$directory => $directory]);
                } catch (Throwable) {
                    //The original error is more relevant, the directory is kept to be removed on destruction
                }
            }

            throw $error;
        }
    }

    public function withClient(
        string $master,
        #[SensitiveParameter] ?ClusterCredentials $credentials,
        callable $callback,
        ?RepositoryRegistry $repositoryRegistry = null,
    ): ScopedClientFactoryInterface {
        $before = $this->directories;

        try {
            $callback(($this)($master, $credentials, $repositoryRegistry));
        } finally {
            $this->delete(array_diff_key($this->directories, $before));
        }

        return $this;
    }

    /**
     * Create a new private directory to hold the credentials files of a client. An existing directory is never
     * reused: it could have been created by someone else.
     */
    private function createDirectory(): string
    {
        $directory = ($this->directoryNameFactory)();

        if ('' === $directory || $this->filesystem->directoryExists($directory)) {
            throw new BadTempFileException('Unable to create a new private directory for the credentials files');
        }

        $this->filesystem->createDirectory(
            $directory,
            [Config::OPTION_DIRECTORY_VISIBILITY => Visibility::PRIVATE],
        );

        $this->directories[$directory] = $directory;

        return $directory;
    }

    /**
     * @param string|null $directory private directory of the client, created at the first file written
     * @param-out string $directory
     */
    private function write(?string &$directory, string $fileName, #[SensitiveParameter] string $value): string
    {
        $directory ??= $this->createDirectory();
        $path = $directory . '/' . $fileName;

        $this->filesystem->write(
            $path,
            $value,
            [Config::OPTION_VISIBILITY => Visibility::PRIVATE],
        );

        return $this->tmpDir . '/' . $path;
    }

    /**
     * Remove all directories, even if a removal fails, and rethrow the first error. Directories not removed are kept
     * to be retried on destruction.
     *
     * @param array<string, string> $directories
     */
    private function delete(array $directories): void
    {
        $error = null;
        foreach ($directories as $directory) {
            try {
                $this->filesystem->deleteDirectory($directory);

                unset($this->directories[$directory]);
            } catch (Throwable $exception) {
                $error ??= $exception;
            }
        }

        if (null !== $error) {
            throw $error;
        }
    }

    public function __destruct()
    {
        try {
            $this->delete($this->directories);
        } catch (Throwable) {
            //A destructor must not throw, directories not removed can not be removed anymore
        }
    }
}
