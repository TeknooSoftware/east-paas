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
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\CheckTimeouts;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(CheckTimeouts::class)]
class CheckTimeoutsTest extends TestCase
{
    public function testInvokeWithoutTimeouts(): void
    {
        $dispatchHistory = $this->createMock(DispatchHistoryInterface::class);
        $dispatchHistory->expects($this->never())->method('__invoke');

        $this->assertInstanceOf(
            CheckTimeouts::class,
            (new CheckTimeouts($dispatchHistory, 120, []))(
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
            CheckTimeouts::class,
            (new CheckTimeouts(
                $dispatchHistory,
                120,
                [
                    'unlimited.null' => null,
                    'unlimited.zero' => 0.0,
                    'unlimited.negative' => -1,
                    'lower' => 60.0,
                    'equal' => 120,
                ],
            ))(
                'foo',
                'bar',
                $this->createStub(JobUnitInterface::class),
            ),
        );
    }

    public function testInvokeWithoutWorkerTimeLimit(): void
    {
        $dispatchHistory = $this->createMock(DispatchHistoryInterface::class);
        $dispatchHistory->expects($this->never())->method('__invoke');

        $this->assertInstanceOf(
            CheckTimeouts::class,
            (new CheckTimeouts($dispatchHistory, 0, ['foo' => 600.0]))(
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
                CheckTimeouts::class . ':Warning',
                [
                    'warnings' => [
                        'The timeout `img_builder` (180s) is bigger than the worker time limit '
                        . '`teknoo.east.paas.worker.time_limit` (120s), the worker will be stopped before reaching '
                        . 'this timeout',
                        'The timeout `kubernetes` (121.5s) is bigger than the worker time limit '
                        . '`teknoo.east.paas.worker.time_limit` (120s), the worker will be stopped before reaching '
                        . 'this timeout',
                    ],
                ],
            )
            ->willReturnSelf();

        $this->assertInstanceOf(
            CheckTimeouts::class,
            (new CheckTimeouts(
                $dispatchHistory,
                120,
                [
                    'git' => null,
                    'img_builder' => 180.0,
                    'docker-compose' => 60.0,
                    'kubernetes' => 121.5,
                ],
            ))(
                'foo',
                'bar',
                $jobUnit,
            ),
        );
    }
}
