<?php

declare(strict_types=1);

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

namespace Teknoo\Tests\East\Paas\Infrastructures\DockerCompose;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\Visibility;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\RunnerInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\EphemeralCredentialsRunner;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Exception\BadTempFileException;
use Teknoo\East\Paas\Infrastructures\DockerCompose\RunnerFactory;
use Teknoo\East\Paas\Infrastructures\DockerCompose\SymfonyProcessRunner;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Teknoo\Recipe\Promise\PromiseInterface;

use function basename;
use function dirname;
use function str_starts_with;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(RunnerFactory::class)]
class RunnerFactoryTest extends TestCase
{
    private string $tmpDir = '/fake/tmp';

    private function buildFactory(
        ?callable $runnerBuilder = null,
        ?callable $directoryNameFactory = null,
        string $playbookBinary = 'ansible-playbook',
        ?float $timeout = null,
        ?FilesystemOperator $filesystem = null,
    ): RunnerFactory {
        return new RunnerFactory(
            filesystem: $filesystem ?? $this->createStub(FilesystemOperator::class),
            tmpDir: $this->tmpDir,
            playbookBinary: $playbookBinary,
            timeout: $timeout,
            directoryNameFactory: $directoryNameFactory,
            runnerBuilder: $runnerBuilder,
        );
    }

    public function testInvokeWithoutCredentialsReturnsRunner(): void
    {
        $factory = $this->buildFactory();

        $runner = $factory('ssh://host:22', null);

        self::assertInstanceOf(SymfonyProcessRunner::class, $runner);

        unset($factory);
    }

    public function testInvokeWithoutMaterializedFileReturnsTheBuiltRunner(): void
    {
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects($this->never())->method('createDirectory');
        $filesystem->expects($this->never())->method('write');

        $built = $this->createStub(RunnerInterface::class);
        $factory = $this->buildFactory(
            runnerBuilder: static fn (): RunnerInterface => $built,
            filesystem: $filesystem,
        );

        self::assertSame($built, $factory('ssh://host:22', new ClusterCredentials(username: 'deployer')));
    }

    public function testInvokeWithMaterializedFileReturnsAnEphemeralCredentialsRunner(): void
    {
        $factory = $this->buildFactory(
            runnerBuilder: fn (): RunnerInterface => $this->createStub(RunnerInterface::class),
        );

        self::assertInstanceOf(
            EphemeralCredentialsRunner::class,
            $factory('ssh://host:22', new ClusterCredentials(clientKey: 'KEY')),
        );
    }

    public function testInvokeWritesPrivateKeyWithPrivateVisibilityAndResolvesUser(): void
    {
        $capturedUser = null;
        $capturedKeyFile = null;
        $capturedBinary = null;
        $capturedTimeout = null;

        //The 0700 / 0600 modes are the LocalFilesystemAdapter's mapping of PRIVATE visibility (out of unit scope);
        //here we assert the documented contract: the key is written with PRIVATE visibility into a new PRIVATE
        //directory.
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects($this->once())
            ->method('directoryExists')
            ->with($this->matchesRegularExpression('#^east-paas-ansible-[0-9a-f]{32}$#'))
            ->willReturn(false);
        $filesystem->expects($this->once())
            ->method('createDirectory')
            ->with(
                $this->matchesRegularExpression('#^east-paas-ansible-[0-9a-f]{32}$#'),
                ['directory_visibility' => Visibility::PRIVATE],
            );
        $filesystem->expects($this->once())
            ->method('write')
            ->with(
                $this->matchesRegularExpression('#^east-paas-ansible-[0-9a-f]{32}/id_key$#'),
                'PRIVATE-KEY-CONTENT' . \PHP_EOL,
                ['visibility' => Visibility::PRIVATE],
            );

        $factory = $this->buildFactory(
            playbookBinary: '/usr/bin/ansible-playbook',
            timeout: 120.0,
            runnerBuilder: function (
                string $binary,
                ?float $timeout,
                ?string $sshUser,
                ?string $privateKeyFile,
            ) use (
                &$capturedBinary,
                &$capturedTimeout,
                &$capturedUser,
                &$capturedKeyFile,
            ): RunnerInterface {
                $capturedBinary = $binary;
                $capturedTimeout = $timeout;
                $capturedUser = $sshUser;
                $capturedKeyFile = $privateKeyFile;

                return $this->createStub(RunnerInterface::class);
            },
            filesystem: $filesystem,
        );

        $credentials = new ClusterCredentials(
            clientKey: 'PRIVATE-KEY-CONTENT',
            username: 'deployer',
        );

        $runner = $factory('ssh://ignored@host:2222', $credentials);

        self::assertInstanceOf(RunnerInterface::class, $runner);
        self::assertSame('/usr/bin/ansible-playbook', $capturedBinary);
        self::assertSame(120.0, $capturedTimeout);
        self::assertSame('deployer', $capturedUser);
        self::assertNotNull($capturedKeyFile);
        self::assertMatchesRegularExpression(
            '#^' . $this->tmpDir . '/east-paas-ansible-[0-9a-f]{32}/id_key$#',
            $capturedKeyFile,
        );

        unset($factory);
    }

    public function testInvokeFallsBackToUserFromUrlWhenUsernameEmpty(): void
    {
        $capturedUser = 'unset';

        $factory = $this->buildFactory(
            runnerBuilder: function (
                string $binary,
                ?float $timeout,
                ?string $sshUser,
                ?string $privateKeyFile,
            ) use (&$capturedUser): RunnerInterface {
                $capturedUser = $sshUser;

                return $this->createStub(RunnerInterface::class);
            },
        );

        $factory('ssh://fromurl@host:22', new ClusterCredentials(clientKey: 'KEY'));

        self::assertSame('fromurl', $capturedUser);

        unset($factory);
    }

    public function testInvokeNoUserAnywhereResolvesNull(): void
    {
        $capturedUser = 'unset';

        $factory = $this->buildFactory(
            runnerBuilder: function (
                string $binary,
                ?float $timeout,
                ?string $sshUser,
                ?string $privateKeyFile,
            ) use (&$capturedUser): RunnerInterface {
                $capturedUser = $sshUser;

                return $this->createStub(RunnerInterface::class);
            },
        );

        $factory('host:22', new ClusterCredentials());

        self::assertNull($capturedUser);

        unset($factory);
    }

    public function testCustomDirectoryNameFactoryIsUsedAndRemovedOnceRun(): void
    {
        $capturedKeyFile = null;
        $deleted = [];

        //The key is written in the custom directory, removed as soon as the runner has run, while the factory, a
        //shared service, is still alive.
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects($this->once())
            ->method('directoryExists')
            ->with('my-dir')
            ->willReturn(false);
        $filesystem->expects($this->once())
            ->method('createDirectory')
            ->with('my-dir', ['directory_visibility' => Visibility::PRIVATE]);
        $filesystem->expects($this->once())
            ->method('write')
            ->with(
                'my-dir/id_key',
                'KEY' . \PHP_EOL,
                ['visibility' => Visibility::PRIVATE],
            );
        $filesystem->expects($this->once())
            ->method('deleteDirectory')
            ->willReturnCallback(
                function (string $path) use (&$deleted): void {
                    $deleted[] = $path;
                }
            );

        $inner = $this->createMock(RunnerInterface::class);
        $inner->expects($this->once())
            ->method('run')
            ->willReturnCallback(
                function () use (&$deleted, $inner): RunnerInterface {
                    self::assertSame([], $deleted);

                    return $inner;
                }
            );

        $factory = $this->buildFactory(
            runnerBuilder: function (
                string $binary,
                ?float $timeout,
                ?string $sshUser,
                ?string $privateKeyFile,
            ) use (
                &$capturedKeyFile,
                $inner,
            ): RunnerInterface {
                $capturedKeyFile = $privateKeyFile;

                return $inner;
            },
            directoryNameFactory: static fn (): string => 'my-dir',
            filesystem: $filesystem,
        );

        $runner = $factory('ssh://host:22', new ClusterCredentials(clientKey: 'KEY'));

        self::assertSame($this->tmpDir . '/my-dir/id_key', $capturedKeyFile);
        self::assertSame([], $deleted);

        $runner->run(
            playbookPath: '/work/deploy.yml',
            inventoryPath: '/work/inventory.ini',
            extraVars: [],
            credentials: null,
            promise: $this->createStub(PromiseInterface::class),
        );

        self::assertSame(['my-dir'], $deleted);

        //Nothing left to remove, neither by the runner nor by the factory
        unset($runner, $factory);
        self::assertSame(['my-dir'], $deleted);
    }

    public function testInvokeRefusesAnExistingDirectory(): void
    {
        //An existing directory could have been created by someone else: it is never reused, nor removed
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects($this->once())
            ->method('directoryExists')
            ->with('my-dir')
            ->willReturn(true);
        $filesystem->expects($this->never())->method('createDirectory');
        $filesystem->expects($this->never())->method('write');
        $filesystem->expects($this->never())->method('deleteDirectory');

        $factory = $this->buildFactory(
            runnerBuilder: fn (): RunnerInterface => $this->createStub(RunnerInterface::class),
            directoryNameFactory: static fn (): string => 'my-dir',
            filesystem: $filesystem,
        );

        $this->expectException(BadTempFileException::class);

        $factory('ssh://host:22', new ClusterCredentials(clientKey: 'KEY'));
    }

    public function testInvokeRefusesAnEmptyDirectoryName(): void
    {
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects($this->never())->method('createDirectory');
        $filesystem->expects($this->never())->method('write');

        $factory = $this->buildFactory(
            runnerBuilder: fn (): RunnerInterface => $this->createStub(RunnerInterface::class),
            directoryNameFactory: static fn (): string => '',
            filesystem: $filesystem,
        );

        $this->expectException(BadTempFileException::class);

        $factory('ssh://host:22', new ClusterCredentials(clientKey: 'KEY'));
    }

    public function testInvokeRemovesTheDirectoryWhenTheRunnerCanNotBeBuilt(): void
    {
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects($this->once())->method('createDirectory');
        $filesystem->expects($this->once())->method('write');
        $filesystem->expects($this->once())
            ->method('deleteDirectory')
            ->with('my-dir');

        $factory = $this->buildFactory(
            runnerBuilder: static fn (): RunnerInterface => throw new RuntimeException('foo'),
            directoryNameFactory: static fn (): string => 'my-dir',
            filesystem: $filesystem,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('foo');

        $factory('ssh://host:22', new ClusterCredentials(clientKey: 'KEY'));
    }

    public function testInvokeRemovesTheDirectoryWhenAWriteFails(): void
    {
        $filesystem = $this->createMock(FilesystemOperator::class);
        //Both files share the same directory, created once
        $filesystem->expects($this->once())->method('createDirectory')->with('my-dir');
        $filesystem->expects($this->exactly(2))
            ->method('write')
            ->willReturnCallback(
                function (string $path): void {
                    if ('my-dir/known_hosts' === $path) {
                        throw new RuntimeException('Unable to write');
                    }
                }
            );
        $filesystem->expects($this->once())
            ->method('deleteDirectory')
            ->with('my-dir');

        $factory = $this->buildFactory(
            runnerBuilder: fn (): RunnerInterface => $this->createStub(RunnerInterface::class),
            directoryNameFactory: static fn (): string => 'my-dir',
            filesystem: $filesystem,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to write');

        $factory(
            'ssh://host:22',
            new ClusterCredentials(caCertificate: 'ssh-rsa AAAAB3', clientKey: 'KEY'),
        );
    }

    public function testInvokeMaterializesKnownHostsFromCaCertificate(): void
    {
        $writes = [];
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects($this->exactly(2))
            ->method('write')
            ->willReturnCallback(function (string $path, string $content, array $config) use (&$writes): void {
                $writes[] = [$path, $content, $config];
            });

        $capturedKnownHosts = null;
        $factory = $this->buildFactory(
            runnerBuilder: function (
                string $binary,
                ?float $timeout,
                ?string $sshUser,
                ?string $privateKeyFile,
                ?string $knownHostsFile,
            ) use (&$capturedKnownHosts): RunnerInterface {
                $capturedKnownHosts = $knownHostsFile;

                return $this->createStub(RunnerInterface::class);
            },
            filesystem: $filesystem,
        );

        $credentials = new ClusterCredentials(
            caCertificate: "ssh-ed25519 AAAAC3Nza host-comment\n\n# ignored\n"
                . "[other.host]:2200 ecdsa-sha2-nistp256 AAAAE2Vj\n",
            clientKey: 'KEY',
            username: 'deployer',
        );

        $factory('ssh://docker.example.com:2222', $credentials);

        self::assertNotNull($capturedKnownHosts);
        self::assertTrue(str_starts_with($capturedKnownHosts, $this->tmpDir . '/'));
        //The key and the known_hosts file share the private directory of the runner
        self::assertSame(dirname($writes[0][0]), dirname($writes[1][0]));
        self::assertSame('known_hosts', basename($writes[1][0]));
        self::assertSame($this->tmpDir . '/' . $writes[1][0], $capturedKnownHosts);
        //Second write is the known_hosts file: a bare public key is bound to the address host (non-default
        //port form), a full known_hosts line is kept as-is, blank/comment lines are dropped.
        self::assertSame(
            "[docker.example.com]:2222 ssh-ed25519 AAAAC3Nza host-comment\n"
            . "[other.host]:2200 ecdsa-sha2-nistp256 AAAAE2Vj\n",
            $writes[1][1],
        );
        self::assertSame(['visibility' => Visibility::PRIVATE], $writes[1][2]);

        unset($factory);
    }

    public function testInvokeKnownHostsUsesBareHostOnDefaultPort(): void
    {
        $writes = [];
        $filesystem = $this->createStub(FilesystemOperator::class);
        $filesystem->method('write')
            ->willReturnCallback(function (string $path, string $content) use (&$writes): void {
                $writes[] = $content;
            });

        $factory = $this->buildFactory(filesystem: $filesystem);
        $factory('docker.example.com', new ClusterCredentials(caCertificate: 'ssh-rsa AAAAB3'));

        self::assertSame(["docker.example.com ssh-rsa AAAAB3\n"], $writes);

        unset($factory);
    }
}
