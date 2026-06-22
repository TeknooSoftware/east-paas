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

use League\Flysystem\FilesystemOperator;
use League\Flysystem\Visibility;
use SensitiveParameter;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\RunnerFactoryInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\RunnerInterface;
use Teknoo\East\Paas\Object\ClusterCredentials;

use function parse_url;
use function uniqid;

use const PHP_URL_USER;

/**
 * Factory in the DI building, on demand, a configured `RunnerInterface` to run an Ansible playbook on the
 * remote Docker host over SSH.
 *
 * The factory materializes the SSH private key extracted from `ClusterCredentials::getClientKey()` into the
 * workspace filesystem with private visibility (the LocalFilesystemAdapter maps it to mode `0600`, which
 * Ansible requires) and resolves the SSH login user from `ClusterCredentials::getUsername()`, falling back
 * to the user embedded in the `cluster.address` (`ssh://user@host:port`). Materialized files are removed in
 * `__destruct()`.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
final class RunnerFactory implements RunnerFactoryInterface
{
    /**
     * @var string[]
     */
    private array $files = [];

    /**
     * @var callable(): string
     */
    private $keyFileNameFactory;

    /**
     * @var callable(string, ?float, ?string, ?string): RunnerInterface
     */
    private $runnerBuilder;

    /**
     * @param FilesystemOperator $filesystem workspace filesystem (rooted at the worker temp dir) used to
     *        materialize the SSH private key
     * @param string $tmpDir absolute path of the workspace filesystem root, used to build the absolute key
     *        path passed to Ansible
     * @param (callable(string, ?float, ?string, ?string): RunnerInterface)|null $runnerBuilder builder of
     *        the concrete `RunnerInterface` (defaults to `SymfonyProcessRunner`; DI may inject another
     *        builder instead)
     * @param (callable(): string)|null $keyFileNameFactory overridable generator of the relative key file
     *        name (defaults to a `uniqid`-suffixed name)
     */
    public function __construct(
        private readonly FilesystemOperator $filesystem,
        private readonly string $tmpDir,
        private readonly string $playbookBinary = 'ansible-playbook',
        private readonly ?float $timeout = null,
        ?callable $keyFileNameFactory = null,
        ?callable $runnerBuilder = null,
    ) {
        if (null !== $keyFileNameFactory) {
            $this->keyFileNameFactory = $keyFileNameFactory;
        } else {
            $this->keyFileNameFactory = static fn (): string => 'east-paas-ansible-' . uniqid('', true);
        }

        if (null !== $runnerBuilder) {
            $this->runnerBuilder = $runnerBuilder;
        } else {
            $this->runnerBuilder = static fn (
                string $playbookBinary,
                ?float $timeout,
                ?string $sshUser,
                ?string $privateKeyFile,
            ): RunnerInterface => new SymfonyProcessRunner(
                playbookBinary: $playbookBinary,
                timeout: $timeout,
                sshUser: $sshUser,
                privateKeyFile: $privateKeyFile,
            );
        }
    }

    public function __invoke(
        string $url,
        #[SensitiveParameter] ?ClusterCredentials $credentials,
    ): RunnerInterface {
        $privateKeyFile = null;
        $sshUser = null;

        if (null !== $credentials) {
            if (!empty($content = $credentials->getUsername())) {
                $sshUser = $content;
            }

            if (!empty($content = $credentials->getClientKey())) {
                $privateKeyFile = $this->write($content);
            }
        }

        if (null === $sshUser) {
            $sshUser = $this->extractUserFromUrl($url);
        }

        return ($this->runnerBuilder)(
            $this->playbookBinary,
            $this->timeout,
            $sshUser,
            $privateKeyFile,
        );
    }

    private function extractUserFromUrl(string $url): ?string
    {
        $user = parse_url($url, PHP_URL_USER);

        if (!empty($user)) {
            return (string) $user;
        }

        return null;
    }

    private function write(#[SensitiveParameter] string $value): string
    {
        $fileName = ($this->keyFileNameFactory)();

        $this->filesystem->write(
            $fileName,
            $value,
            ['visibility' => Visibility::PRIVATE],
        );

        $this->files[] = $fileName;

        return $this->tmpDir . '/' . $fileName;
    }

    private function delete(): void
    {
        foreach ($this->files as $file) {
            if ($this->filesystem->fileExists($file)) {
                $this->filesystem->delete($file);
            }
        }

        $this->files = [];
    }

    public function __destruct()
    {
        $this->delete();
    }
}
