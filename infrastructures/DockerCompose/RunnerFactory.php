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

namespace Teknoo\East\Paas\Infrastructures\DockerCompose;

use League\Flysystem\Config;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Visibility;
use SensitiveParameter;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\RunnerFactoryInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\RunnerInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Exception\BadTempFileException;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Throwable;

use function bin2hex;
use function count;
use function explode;
use function implode;
use function parse_url;
use function preg_split;
use function random_bytes;
use function str_starts_with;
use function trim;

use const PHP_EOL;
use const PHP_URL_HOST;
use const PHP_URL_PATH;
use const PHP_URL_PORT;
use const PHP_URL_USER;

/**
 * Factory in the DI building, on demand, a configured `RunnerInterface` to run an Ansible playbook on the
 * remote Docker host over SSH.
 *
 * The factory materializes the SSH private key extracted from `ClusterCredentials::getClientKey()` into the
 * workspace filesystem with private visibility (the LocalFilesystemAdapter maps it to mode `0600`, which
 * Ansible requires) and resolves the SSH login user from `ClusterCredentials::getUsername()`, falling back
 * to the user embedded in the `cluster.address` (`ssh://user@host:port`). When
 * `ClusterCredentials::getCaCertificate()` carries the host's SSH public key(s), a `known_hosts` file is
 * materialized too so the runner can enforce a strict host key checking.
 *
 * These files are written into a new private directory (mode `0700`), with an unpredictable name, dedicated to the
 * runner: the LocalFilesystemAdapter applies the visibility of a file after having written it, the directory
 * prevents others users of the worker to read it meanwhile.
 *
 * The factory is a shared service living as long as the worker: this directory is not kept by it but owned by the
 * returned runner, an `EphemeralCredentialsRunner`, which removes it as soon as the playbook has run (or on its
 * destruction if it is never run). The directory is removed when the runner can not be built.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
final class RunnerFactory implements RunnerFactoryInterface
{
    /**
     * @var callable(): string
     */
    private $directoryNameFactory;

    /**
     * @var callable(string, ?float, ?string, ?string, ?string): RunnerInterface
     */
    private $runnerBuilder;

    /**
     * @param FilesystemOperator $filesystem workspace filesystem (rooted at the worker temp dir) used to
     *        materialize the SSH private key
     * @param string $tmpDir absolute path of the workspace filesystem root, used to build the absolute key
     *        path passed to Ansible
     * @param (callable(string, ?float, ?string, ?string, ?string): RunnerInterface)|null $runnerBuilder
     *        builder of the concrete `RunnerInterface` (defaults to `SymfonyProcessRunner`; DI may inject
     *        another builder instead)
     * @param (callable(): string)|null $directoryNameFactory overridable generator of the name of the private
     *        directory, relative to the workspace filesystem, holding the credentials files of a runner (defaults to
     *        a random name, it must be unpredictable)
     */
    public function __construct(
        private readonly FilesystemOperator $filesystem,
        private readonly string $tmpDir,
        private readonly string $playbookBinary = 'ansible-playbook',
        private readonly ?float $timeout = null,
        ?callable $directoryNameFactory = null,
        ?callable $runnerBuilder = null,
    ) {
        if (null !== $directoryNameFactory) {
            $this->directoryNameFactory = $directoryNameFactory;
        } else {
            $this->directoryNameFactory = static fn (): string => 'east-paas-ansible-' . bin2hex(random_bytes(16));
        }

        if (null !== $runnerBuilder) {
            $this->runnerBuilder = $runnerBuilder;
        } else {
            $this->runnerBuilder = static fn (
                string $playbookBinary,
                ?float $timeout,
                ?string $sshUser,
                ?string $privateKeyFile,
                ?string $knownHostsFile = null,
            ): RunnerInterface => new SymfonyProcessRunner(
                playbookBinary: $playbookBinary,
                timeout: $timeout,
                sshUser: $sshUser,
                privateKeyFile: $privateKeyFile,
                knownHostsFile: $knownHostsFile,
            );
        }
    }

    public function __invoke(
        string $url,
        #[SensitiveParameter] ?ClusterCredentials $credentials,
    ): RunnerInterface {
        $privateKeyFile = null;
        $knownHostsFile = null;
        $sshUser = null;
        $directory = null;

        try {
            if (null !== $credentials) {
                if (!empty($content = $credentials->getUsername())) {
                    $sshUser = $content;
                }

                if (!empty($content = $credentials->getClientKey())) {
                    $privateKeyFile = $this->write($directory, 'id_key', $content);
                }

                if (!empty($content = $credentials->getCaCertificate())) {
                    $knownHostsFile = $this->write($directory, 'known_hosts', $this->buildKnownHosts($url, $content));
                }
            }

            if (null === $sshUser) {
                $sshUser = $this->extractUserFromUrl($url);
            }

            $runner = ($this->runnerBuilder)(
                $this->playbookBinary,
                $this->timeout,
                $sshUser,
                $privateKeyFile,
                $knownHostsFile,
            );
        } catch (Throwable $error) {
            if (null !== $directory) {
                $this->filesystem->deleteDirectory($directory);
            }

            throw $error;
        }

        if (null === $directory) {
            return $runner;
        }

        return new EphemeralCredentialsRunner(
            runner: $runner,
            filesystem: $this->filesystem,
            directory: $directory,
        );
    }

    /**
     * Build the content of a `known_hosts` file from the host public key(s) carried by the credentials: a
     * line already in the known_hosts format (`host type key`) is kept as-is, a bare public key
     * (`type key [comment]`) is bound to the cluster address host (`[host]:port` for a non-default port).
     */
    private function buildKnownHosts(string $url, string $publicKeys): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (empty($host)) {
            //No scheme: parse_url returns the whole string as path for "host:port" / "host"
            $path = (string) (parse_url($url, PHP_URL_PATH) ?: $url);
            $parts = explode(':', $path, 2);
            $host = $parts[0];
            $port = $parts[1] ?? null;
        } else {
            $port = parse_url($url, PHP_URL_PORT);
        }

        $hostPattern = (string) $host;
        if (!empty($port) && 22 !== (int) $port) {
            $hostPattern = '[' . $host . ']:' . $port;
        }

        $lines = [];
        foreach ((array) preg_split('#\r?\n#', trim($publicKeys)) as $line) {
            $line = trim((string) $line);
            if ('' === $line || str_starts_with($line, '#')) {
                continue;
            }

            $fields = (array) preg_split('#\s+#', $line);
            //"ssh-ed25519 AAAA..." (bare public key) vs "host ssh-ed25519 AAAA..." (known_hosts line)
            $first = (string) $fields[0];
            if (count($fields) >= 3 && !str_starts_with($first, 'ssh-') && !str_starts_with($first, 'ecdsa-')) {
                $lines[] = $line;

                continue;
            }

            $lines[] = $hostPattern . ' ' . $line;
        }

        return implode(PHP_EOL, $lines);
    }

    private function extractUserFromUrl(string $url): ?string
    {
        $user = parse_url($url, PHP_URL_USER);

        if (!empty($user)) {
            return (string) $user;
        }

        return null;
    }

    /**
     * Create a new private directory to hold the credentials files of a runner. An existing directory is never
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

        return $directory;
    }

    /**
     * @param string|null $directory private directory of the runner, created at the first file written
     * @param-out string $directory
     */
    private function write(?string &$directory, string $fileName, #[SensitiveParameter] string $value): string
    {
        $directory ??= $this->createDirectory();
        $path = $directory . '/' . $fileName;

        $this->filesystem->write(
            $path,
            trim($value) . PHP_EOL,
            [Config::OPTION_VISIBILITY => Visibility::PRIVATE],
        );

        return $this->tmpDir . '/' . $path;
    }
}
