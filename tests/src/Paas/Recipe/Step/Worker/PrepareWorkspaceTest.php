<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\Worker;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\PrepareWorkspace;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @covers \Teknoo\East\Paas\Recipe\Step\Worker\PrepareWorkspace
 */
class PrepareWorkspaceTest extends TestCase
{
    /**
     * @var JobWorkspaceInterface
     */
    private $workspace;

    /**
     * @return JobWorkspaceInterface|MockObject
     */
    public function getWorkspaceMock(): JobWorkspaceInterface
    {
        if (!$this->workspace instanceof JobWorkspaceInterface) {
            $this->workspace = $this->createMock(JobWorkspaceInterface::class);
        }

        return $this->workspace;
    }

    public function buildStep(): PrepareWorkspace
    {
        return new PrepareWorkspace($this->getWorkspaceMock());
    }

    public function testInvokeBadJob()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(
            new \stdClass(),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadManager()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(
            $this->createMock(JobUnitInterface::class),
            new \stdClass()
        );
    }

    public function testInvoke()
    {
        $job = $this->createMock(JobUnitInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $this->getWorkspaceMock()->expects(self::once())
            ->method('setJob')
            ->with($job)
            ->willReturnSelf();

        $manager->expects(self::once())
            ->method('updateWorkPlan')
            ->with([JobWorkspaceInterface::class => $this->getWorkspaceMock()]);

        self::assertInstanceOf(
            PrepareWorkspace::class,
            ($this->buildStep())($job, $manager)
        );
    }
}
