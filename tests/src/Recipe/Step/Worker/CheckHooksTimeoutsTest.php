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

namespace Teknoo\Tests\East\Paas\Recipe\Step\Worker;

use PHPUnit\Framework\Attributes\CoversClass;
use RangeException;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\East\Paas\Contracts\Hook\TimeoutAwareHookInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\CheckHooksTimeouts;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(CheckHooksTimeouts::class)]
class CheckHooksTimeoutsTest extends TestCase
{
    /**
     * @param array<string, HookInterface> $hooks
     */
    private function buildCompiledDeployment(array $hooks): CompiledDeploymentInterface
    {
        $compiledDeployment = $this->createStub(CompiledDeploymentInterface::class);
        $compiledDeployment->method('foreachHook')
            ->willReturnCallback(
                static function (callable $callback) use ($hooks, $compiledDeployment): CompiledDeploymentInterface {
                    foreach ($hooks as $name => $hook) {
                        $callback($hook, $name);
                    }

                    return $compiledDeployment;
                }
            );

        return $compiledDeployment;
    }

    private function buildHook(bool $tooBig): TimeoutAwareHookInterface
    {
        $hook = $this->createStub(TimeoutAwareHookInterface::class);
        $hook->method('checkTimeLimit')
            ->willReturnCallback(
                static function (int $timeLimit, PromiseInterface $promise) use ($tooBig, $hook): TimeoutAwareHookInterface {
                    if ($tooBig) {
                        $promise->fail(new RangeException("Too big for {$timeLimit}s"));
                    } else {
                        $promise->success();
                    }

                    return $hook;
                }
            );

        return $hook;
    }

    public function testInvokeWithoutHooks(): void
    {
        $dispatchHistory = $this->createMock(DispatchHistoryInterface::class);
        $dispatchHistory->expects($this->never())->method('__invoke');

        $this->assertInstanceOf(
            CheckHooksTimeouts::class,
            (new CheckHooksTimeouts($dispatchHistory, 120))(
                $this->buildCompiledDeployment([]),
                'foo',
                'bar',
                $this->createStub(JobUnitInterface::class),
            ),
        );
    }

    public function testInvokeWithoutWarning(): void
    {
        $dispatchHistory = $this->createMock(DispatchHistoryInterface::class);
        $dispatchHistory->expects($this->never())->method('__invoke');

        $this->assertInstanceOf(
            CheckHooksTimeouts::class,
            (new CheckHooksTimeouts($dispatchHistory, 120))(
                $this->buildCompiledDeployment([
                    'build:not-aware' => $this->createStub(HookInterface::class),
                    'build:composer' => $this->buildHook(false),
                    'build:make' => $this->buildHook(false),
                ]),
                'foo',
                'bar',
                $this->createStub(JobUnitInterface::class),
            ),
        );
    }

    public function testInvokeWithWarnings(): void
    {
        $jobUnit = $this->createStub(JobUnitInterface::class);
        $jobUnit->method('getId')->willReturn('job-id');

        $dispatchHistory = $this->createMock(DispatchHistoryInterface::class);
        $dispatchHistory->expects($this->once())
            ->method('__invoke')
            ->with(
                'foo',
                'bar',
                'job-id',
                CheckHooksTimeouts::class . ':Warning',
                [
                    'warnings' => [
                        'Hook `build:composer`: Too big for 120s',
                        'Hook `build:npm`: Too big for 120s',
                    ],
                ],
            )
            ->willReturnSelf();

        $this->assertInstanceOf(
            CheckHooksTimeouts::class,
            (new CheckHooksTimeouts($dispatchHistory, 120))(
                $this->buildCompiledDeployment([
                    'build:not-aware' => $this->createStub(HookInterface::class),
                    'build:composer' => $this->buildHook(true),
                    'build:make' => $this->buildHook(false),
                    'build:npm' => $this->buildHook(true),
                ]),
                'foo',
                'bar',
                $jobUnit,
            ),
        );
    }
}
