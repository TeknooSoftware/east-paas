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
use RuntimeException;
use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\RunnerInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\SymfonyProcessRunner;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(SymfonyProcessRunner::class)]
class SymfonyProcessRunnerTest extends TestCase
{
    public function testRunSuccessBuildsCommandAndResolvesPromise(): void
    {
        $capturedCommand = null;
        $capturedTimeout = null;

        $process = $this->createMock(Process::class);
        $process->expects($this->once())->method('run');
        $process->method('isSuccessful')->willReturn(true);
        $process->method('getOutput')->willReturn('PLAY RECAP ok=2');
        $process->method('getErrorOutput')->willReturn('');

        $runner = new SymfonyProcessRunner(
            playbookBinary: '/usr/bin/ansible-playbook',
            timeout: 300.0,
            sshUser: 'deployer',
            privateKeyFile: '/tmp/key',
            processFactory: function (
                array $command,
                ?float $timeout
            ) use (
                $process,
                &$capturedCommand,
                &$capturedTimeout,
            ): Process {
                $capturedCommand = $command;
                $capturedTimeout = $timeout;

                return $process;
            },
        );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->with($this->stringContains('PLAY RECAP ok=2'));
        $promise->expects($this->never())->method('fail');

        $result = $runner->run(
            '/run/deploy.yml',
            '/run/inventory.ini',
            ['compose_project' => 'demo'],
            new ClusterCredentials(),
            $promise,
        );

        self::assertInstanceOf(RunnerInterface::class, $result);
        self::assertSame(300.0, $capturedTimeout);
        self::assertSame(
            [
                '/usr/bin/ansible-playbook',
                '/run/deploy.yml',
                '--inventory',
                '/run/inventory.ini',
                '--extra-vars',
                '{"compose_project":"demo"}',
                '--user',
                'deployer',
                '--private-key',
                '/tmp/key',
            ],
            $capturedCommand,
        );
    }

    public function testRunOmitsUserAndKeyWhenAbsent(): void
    {
        $capturedCommand = null;

        $process = $this->createStub(Process::class);
        $process->method('isSuccessful')->willReturn(true);
        $process->method('getOutput')->willReturn('ok');
        $process->method('getErrorOutput')->willReturn('');

        $runner = new SymfonyProcessRunner(
            processFactory: function (array $command, ?float $timeout) use ($process, &$capturedCommand): Process {
                $capturedCommand = $command;

                return $process;
            },
        );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');

        $runner->run('/run/deploy.yml', '/run/inv.ini', [], null, $promise);

        self::assertSame(
            [
                'ansible-playbook',
                '/run/deploy.yml',
                '--inventory',
                '/run/inv.ini',
                '--extra-vars',
                '[]',
            ],
            $capturedCommand,
        );
    }

    public function testRunFailureResolvesPromiseWithFail(): void
    {
        $process = $this->createStub(Process::class);
        $process->method('isSuccessful')->willReturn(false);
        $process->method('getErrorOutput')->willReturn('fatal: ssh connection refused');

        $runner = new SymfonyProcessRunner(
            processFactory: fn (array $command, ?float $timeout): Process => $process,
        );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())
            ->method('fail')
            ->with($this->callback(
                static fn (RuntimeException $e): bool => str_contains($e->getMessage(), 'ssh connection refused'),
            ));

        $runner->run('/run/deploy.yml', '/run/inv.ini', [], null, $promise);
    }

    public function testRunCatchesThrownExceptionAndFails(): void
    {
        $runner = new SymfonyProcessRunner(
            processFactory: function (): Process {
                throw new RuntimeException('cannot spawn process');
            },
        );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())
            ->method('fail')
            ->with($this->isInstanceOf(RuntimeException::class));

        $runner->run('/run/deploy.yml', '/run/inv.ini', [], null, $promise);
    }
}
