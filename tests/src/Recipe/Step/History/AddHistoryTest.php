<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
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
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\History;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Recipe\Step\History\AddHistory;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(AddHistory::class)]
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

        $job->expects($this->once())
            ->method('addFromHistory')
            ->with($history)
            ->willReturnCallback(function (History $h, callable $f) use ($job) {
                $f($h);

                return $job;
            });

        $manager->expects($this->once())->method('updateWorkPlan')
            ->with([History::class => $history])
            ->willReturnSelf();

        self::assertInstanceOf(
            AddHistory::class,
            ($this->buildStep())($job, $history, $manager)
        );
    }
}
