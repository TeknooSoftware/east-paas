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

namespace Teknoo\Tests\East\Paas\Recipe\Step\History;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Recipe\Step\History\AddHistory;
use TypeError;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(AddHistory::class)]
class AddHistoryTest extends TestCase
{
    public function buildStep(): AddHistory
    {
        return new AddHistory();
    }

    public function testInvokeBadJob(): void
    {
        $this->expectException(TypeError::class);
        $this->buildStep()->__invoke(
            new stdClass(),
            $this->createStub(History::class),
            $this->createStub(ManagerInterface::class)
        );
    }

    public function testInvokeBadHistory(): void
    {
        $this->expectException(TypeError::class);
        $this->buildStep()->__invoke(
            $this->createStub(Job::class),
            new stdClass(),
            $this->createStub(ManagerInterface::class)
        );
    }

    public function testInvokeBadManager(): void
    {
        $this->expectException(TypeError::class);
        $this->buildStep()->__invoke(
            $this->createStub(Job::class),
            $this->createStub(History::class),
            new stdClass()
        );
    }

    public function testInvoke(): void
    {
        $job = $this->createMock(Job::class);
        $history = $this->createStub(History::class);
        $manager = $this->createMock(ManagerInterface::class);

        $job->expects($this->once())
            ->method('addFromHistory')
            ->with($history)
            ->willReturnCallback(function (History $h, callable $f) use ($job): MockObject|Stub {
                $f($h);

                return $job;
            });

        $manager->expects($this->once())->method('updateWorkPlan')
            ->with([History::class => $history])
            ->willReturnSelf();

        $this->assertInstanceOf(AddHistory::class, ($this->buildStep())($job, $history, $manager));
    }
}
