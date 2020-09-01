<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\History;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Recipe\Step\History\AddHistory;

/**
 * @covers \Teknoo\East\Paas\Recipe\Step\History\AddHistory
 */
class AddHistoryTest extends TestCase
{
    public function buildStep(): AddHistory
    {
        return new AddHistory();
    }

    public function testInvokeBadJob()
    {
        $this->expectException(\TypeError::class);
        $this->buildStep()->__invoke(
            new \stdClass(),
            $this->createMock(History::class),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadHistory()
    {
        $this->expectException(\TypeError::class);
        $this->buildStep()->__invoke(
            $this->createMock(Job::class),
            new \stdClass(),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadManager()
    {
        $this->expectException(\TypeError::class);
        $this->buildStep()->__invoke(
            $this->createMock(Job::class),
            $this->createMock(History::class),
            new \stdClass()
        );
    }

    public function testInvoke()
    {
        $job = $this->createMock(Job::class);
        $history = $this->createMock(History::class);
        $manager = $this->createMock(ManagerInterface::class);

        $job->expects(self::once())
            ->method('addFromHistory')
            ->with($history)
            ->willReturnCallback(function (History $h, callable $f) use ($job) {
                $f($h);

                return $job;
            });

        $manager->expects(self::once())->method('updateWorkPlan')
            ->with([History::class => $history])
            ->willReturnSelf();

        self::assertInstanceOf(
            AddHistory::class,
            ($this->buildStep())($job, $history, $manager)
        );
    }
}
