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

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\RunnerInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\RunnerFactory;
use Teknoo\East\Paas\Infrastructures\DockerCompose\SymfonyProcessRunner;
use Teknoo\East\Paas\Object\ClusterCredentials;

use function file_exists;
use function file_get_contents;
use function fileperms;
use function sprintf;
use function substr;
use function sys_get_temp_dir;
use function uniqid;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(RunnerFactory::class)]
class RunnerFactoryTest extends TestCase
{
    private string $tmpDir = '';

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/east-paas-rf-' . uniqid('', true);
    }

    private function buildFactory(
        ?callable $runnerBuilder = null,
        ?callable $keyFileNameFactory = null,
        string $playbookBinary = 'ansible-playbook',
        ?float $timeout = null,
    ): RunnerFactory {
        return new RunnerFactory(
            filesystem: new Filesystem(new LocalFilesystemAdapter($this->tmpDir)),
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

    public function testInvokeWritesPrivateKeyChmod0600AndResolvesUser(): void
    {
        $capturedUser = null;
        $capturedKeyFile = null;
        $capturedBinary = null;
        $capturedTimeout = null;

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
        self::assertTrue(file_exists($capturedKeyFile));
        self::assertSame('PRIVATE-KEY-CONTENT', file_get_contents($capturedKeyFile));
        self::assertSame('0600', substr(sprintf('%o', fileperms($capturedKeyFile)), -4));

        unset($factory);

        self::assertFalse(file_exists($capturedKeyFile));
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
        );

        $factory('ssh://host:22', new ClusterCredentials(clientKey: 'KEY'));

        self::assertSame($this->tmpDir . '/my-key-file', $capturedKeyFile);
        self::assertSame('KEY', file_get_contents($capturedKeyFile));

        unset($factory);

        self::assertFalse(file_exists($capturedKeyFile));
    }
}
