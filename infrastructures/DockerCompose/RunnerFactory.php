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

use function count;
use function explode;
use function implode;
use function parse_url;
use function preg_split;
use function str_starts_with;
use function trim;
use function uniqid;

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
 * materialized too so the runner can enforce a strict host key checking. Materialized files are removed in
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

        if (null !== $credentials) {
            if (!empty($content = $credentials->getUsername())) {
                $sshUser = $content;
            }

            if (!empty($content = $credentials->getClientKey())) {
                $privateKeyFile = $this->write($content);
            }

            if (!empty($content = $credentials->getCaCertificate())) {
                $knownHostsFile = $this->write($this->buildKnownHosts($url, $content));
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
            $knownHostsFile,
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

    private function write(#[SensitiveParameter] string $value): string
    {
        $fileName = ($this->keyFileNameFactory)();

        $this->filesystem->write(
            $fileName,
            trim($value) . PHP_EOL,
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
