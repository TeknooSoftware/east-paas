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

use Asm\Ansible\Command\AnsiblePlaybookInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Teknoo\East\Paas\Infrastructures\DockerCompose\AnsibleRunner;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\RunnerInterface;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(AnsibleRunner::class)]
class AnsibleRunnerTest extends TestCase
{
    /**
     * @param int|string $exitCode value returned by `execute()`
     * @param string $output text streamed through the execute() callback
     * @return array{0: AnsiblePlaybookInterface, 1: \ArrayObject<string, mixed>}
     */
    private function buildPlaybookMock(int|string $exitCode, string $output = ''): array
    {
        /** @var \ArrayObject<string, mixed> $captured */
        $captured = new \ArrayObject([
            'inventory' => null,
            'extraVars' => null,
            'user' => null,
            'privateKey' => null,
        ]);

        $playbook = $this->createStub(AnsiblePlaybookInterface::class);

        $playbook->method('inventoryFile')->willReturnCallback(
            static function (string $i) use ($playbook, $captured): AnsiblePlaybookInterface {
                $captured['inventory'] = $i;

                return $playbook;
            }
        );
        $playbook->method('extraVars')->willReturnCallback(
            static function (string|array $v) use ($playbook, $captured): AnsiblePlaybookInterface {
                $captured['extraVars'] = $v;

                return $playbook;
            }
        );
        $playbook->method('user')->willReturnCallback(
            static function (string $u) use ($playbook, $captured): AnsiblePlaybookInterface {
                $captured['user'] = $u;

                return $playbook;
            }
        );
        $playbook->method('privateKey')->willReturnCallback(
            static function (string $k) use ($playbook, $captured): AnsiblePlaybookInterface {
                $captured['privateKey'] = $k;

                return $playbook;
            }
        );
        $playbook->method('execute')->willReturnCallback(
            static function (?callable $cb) use ($exitCode, $output): int|string {
                if (null !== $cb && '' !== $output) {
                    $cb('out', $output);
                }

                return $exitCode;
            }
        );

        return [$playbook, $captured];
    }

    private function buildRunner(
        AnsiblePlaybookInterface $playbook,
        ?int $timeout = null,
        ?string $sshUser = null,
        ?string $keyFile = null,
        ?string &$capturedPath = null,
    ): AnsibleRunner {
        return new AnsibleRunner(
            playbookBinary: 'ansible-playbook',
            timeout: $timeout,
            sshUser: $sshUser,
            privateKeyFile: $keyFile,
            playbookFactory: static function (string $playbookPath) use (
                $playbook,
                &$capturedPath,
            ): AnsiblePlaybookInterface {
                $capturedPath = $playbookPath;

                return $playbook;
            },
        );
    }

    public function testRunSuccessResolvesPromiseWithOutput(): void
    {
        [$playbook, $captured] = $this->buildPlaybookMock(0, 'PLAY RECAP ok=3');

        $capturedPath = null;
        $runner = $this->buildRunner(
            playbook: $playbook,
            timeout: 200,
            sshUser: 'deployer',
            keyFile: '/tmp/key',
            capturedPath: $capturedPath,
        );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->with($this->stringContains('PLAY RECAP ok=3'));
        $promise->expects($this->never())->method('fail');

        $result = $runner->run(
            '/var/run/deploy/deploy.yml',
            '/var/run/deploy/inventory.ini',
            ['compose_project' => 'demo'],
            new ClusterCredentials(),
            $promise,
        );

        self::assertInstanceOf(RunnerInterface::class, $result);
        self::assertSame('/var/run/deploy/deploy.yml', $capturedPath);
        self::assertSame('/var/run/deploy/inventory.ini', $captured['inventory']);
        self::assertSame(['compose_project' => 'demo'], $captured['extraVars']);
        self::assertSame('deployer', $captured['user']);
        self::assertSame('/tmp/key', $captured['privateKey']);
    }

    public function testRunFailureResolvesPromiseWithFail(): void
    {
        [$playbook] = $this->buildPlaybookMock(2, 'fatal: task failed');

        $runner = $this->buildRunner(playbook: $playbook);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())
            ->method('fail')
            ->with($this->callback(
                static fn (RuntimeException $e): bool => str_contains($e->getMessage(), 'fatal: task failed'),
            ));

        $runner->run(
            '/run/deploy.yml',
            '/run/inventory.ini',
            [],
            null,
            $promise,
        );
    }

    public function testRunDoesNotSetUserOrKeyWhenAbsent(): void
    {
        [$playbook, $captured] = $this->buildPlaybookMock(0, 'ok');

        $runner = $this->buildRunner(playbook: $playbook);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');

        $runner->run('/run/deploy.yml', '/run/inv.ini', [], null, $promise);

        self::assertNull($captured['user']);
        self::assertNull($captured['privateKey']);
    }

    public function testRunCatchesThrownExceptionAndFails(): void
    {
        $runner = new AnsibleRunner(
            playbookFactory: static function (): AnsiblePlaybookInterface {
                throw new RuntimeException('ansible binary not found');
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
