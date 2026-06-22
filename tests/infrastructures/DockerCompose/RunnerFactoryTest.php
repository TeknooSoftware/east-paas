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
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\RunnerInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\RunnerFactory;
use Teknoo\East\Paas\Infrastructures\DockerCompose\SymfonyProcessRunner;
use Teknoo\East\Paas\Object\ClusterCredentials;

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
        ?callable $keyFileNameFactory = null,
        string $playbookBinary = 'ansible-playbook',
        ?float $timeout = null,
        ?FilesystemOperator $filesystem = null,
    ): RunnerFactory {
        return new RunnerFactory(
            filesystem: $filesystem ?? $this->createStub(FilesystemOperator::class),
            tmpDir: $this->tmpDir,
            playbookBinary: $playbookBinary,
            timeout: $timeout,
            keyFileNameFactory: $keyFileNameFactory,
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

    public function testInvokeWritesPrivateKeyWithPrivateVisibilityAndResolvesUser(): void
    {
        $capturedUser = null;
        $capturedKeyFile = null;
        $capturedBinary = null;
        $capturedTimeout = null;

        //The 0600 mode is the LocalFilesystemAdapter's mapping of PRIVATE visibility (out of unit scope);
        //here we assert the documented contract: the key is written with PRIVATE visibility.
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects($this->once())
            ->method('write')
            ->with(
                $this->anything(),
                'PRIVATE-KEY-CONTENT',
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
        self::assertTrue(str_starts_with($capturedKeyFile, $this->tmpDir . '/'));

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

    public function testCustomKeyFileNameFactoryIsUsedAndCleanedUp(): void
    {
        $capturedKeyFile = null;

        //The key is written under the custom name, then removed on __destruct (fileExists + delete).
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects($this->once())
            ->method('write')
            ->with(
                'my-key-file',
                'KEY',
                ['visibility' => Visibility::PRIVATE],
            );
        $filesystem->expects($this->once())
            ->method('fileExists')
            ->with('my-key-file')
            ->willReturn(true);
        $filesystem->expects($this->once())
            ->method('delete')
            ->with('my-key-file');

        $factory = $this->buildFactory(
            runnerBuilder: function (
                string $binary,
                ?float $timeout,
                ?string $sshUser,
                ?string $privateKeyFile,
            ) use (&$capturedKeyFile): RunnerInterface {
                $capturedKeyFile = $privateKeyFile;

                return $this->createStub(RunnerInterface::class);
            },
            keyFileNameFactory: static fn (): string => 'my-key-file',
            filesystem: $filesystem,
        );

        $factory('ssh://host:22', new ClusterCredentials(clientKey: 'KEY'));

        self::assertSame($this->tmpDir . '/my-key-file', $capturedKeyFile);

        unset($factory);
    }
}
