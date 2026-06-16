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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\RunnerInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Exception\BadTempFileException;
use Teknoo\East\Paas\Infrastructures\DockerCompose\RunnerFactory;
use Teknoo\East\Paas\Infrastructures\DockerCompose\SymfonyProcessRunner;
use Teknoo\East\Paas\Object\ClusterCredentials;

use function file_exists;
use function file_get_contents;
use function fileperms;
use function sys_get_temp_dir;
use function tempnam;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(RunnerFactory::class)]
class RunnerFactoryTest extends TestCase
{
    public function testInvokeWithoutCredentialsReturnsRunner(): void
    {
        $factory = new RunnerFactory(sys_get_temp_dir());

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

        $factory = new RunnerFactory(
            tmpDir: sys_get_temp_dir(),
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
        self::assertSame('0600', \substr(\sprintf('%o', fileperms($capturedKeyFile)), -4));

        unset($factory);

        self::assertFalse(file_exists($capturedKeyFile));
    }

    public function testInvokeFallsBackToUserFromUrlWhenUsernameEmpty(): void
    {
        $capturedUser = 'unset';

        $factory = new RunnerFactory(
            tmpDir: sys_get_temp_dir(),
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

        $factory = new RunnerFactory(
            tmpDir: sys_get_temp_dir(),
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

    public function testInvokeThrowsOnBadTempFileName(): void
    {
        $factory = new RunnerFactory(
            tmpDir: sys_get_temp_dir(),
            tmpNameFunction: fn (): false => false,
        );

        $this->expectException(BadTempFileException::class);

        $factory('ssh://host:22', new ClusterCredentials(clientKey: 'KEY'));
    }

    public function testCustomTmpNameFunctionIsUsed(): void
    {
        $target = tempnam(sys_get_temp_dir(), 'east-paas-test-');
        self::assertNotFalse($target);

        $factory = new RunnerFactory(
            tmpDir: sys_get_temp_dir(),
            tmpNameFunction: fn (): string => $target,
            runnerBuilder: fn (): RunnerInterface => $this->createStub(RunnerInterface::class),
        );

        $factory('ssh://host:22', new ClusterCredentials(clientKey: 'KEY'));

        self::assertSame('KEY', file_get_contents($target));

        unset($factory);

        self::assertFalse(file_exists($target));
    }
}
