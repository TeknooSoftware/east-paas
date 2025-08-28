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

namespace Teknoo\Tests\East\Paas\Compilation\CompiledDeployment;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\CompletionMode;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\SuccessCondition;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\Planning;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Job::class)]
class JobTest extends TestCase
{
    private function createJob(): Job
    {
        return new Job(
            name: 'foo',
            pods: [
                'bar' => $this->createMock(Pod::class),
            ],
            completionsCount: 1,
            isParallel: true,
            completion: CompletionMode::Common,
            successCondition: new SuccessCondition([0], [1]),
            timeLimit: 100,
            shelfLife: 200,
            planning: Planning::Scheduled,
            planningSchedule: '* * * *'
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals('foo', $this->createJob()->getName());
    }

    public function testGetPods(): void
    {
        $this->assertEquals(['bar' => $this->createMock(Pod::class)], $this->createJob()->getPods());
    }

    public function testGetCompletionsCount(): void
    {
        $this->assertEquals(1, $this->createJob()->getCompletionsCount());
    }

    public function testIsParallel(): void
    {
        $this->assertTrue($this->createJob()->isParallel());
    }

    public function testGetCompletion(): void
    {
        $this->assertEquals(CompletionMode::Common, $this->createJob()->getCompletion());
    }

    public function testGetSuccessCondition(): void
    {
        $this->assertEquals(new SuccessCondition([0], [1]), $this->createJob()->getSuccessCondition());
    }

    public function testGetTimeLimit(): void
    {
        $this->assertEquals(100, $this->createJob()->getTimeLimit());
    }

    public function testGetShelfLife(): void
    {
        $this->assertEquals(200, $this->createJob()->getShelfLife());
    }

    public function testGetPlanning(): void
    {
        $this->assertEquals(Planning::Scheduled, $this->createJob()->getPlanning());
    }

    public function testGetPlanningSchedule(): void
    {
        $this->assertEquals('* * * *', $this->createJob()->getPlanningSchedule());
    }
}
