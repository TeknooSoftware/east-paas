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
use Teknoo\East\Paas\Contracts\Conductor\ConductorInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureConductor;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @covers \Teknoo\East\Paas\Recipe\Step\Worker\ConfigureConductor
 */
class ConfigureConductorTest extends TestCase
{
    /**
     * @var ConductorInterface
     */
    private $conductor;

    /**
     * @return ConductorInterface|MockObject
     */
    public function getConductorMock(): ConductorInterface
    {
        if (!$this->conductor instanceof ConductorInterface) {
            $this->conductor = $this->createMock(ConductorInterface::class);
        }

        return $this->conductor;
    }

    public function buildStep(): ConfigureConductor
    {
        return new ConfigureConductor($this->getConductorMock());
    }

    public function testInvokeBadJob()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(
            new \stdClass(),
            $this->createMock(JobWorkspaceInterface::class),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadWorkspace()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(
            $this->createMock(JobUnitInterface::class),
            new \stdClass(),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadManager()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(
            $this->createMock(JobUnitInterface::class),
            $this->createMock(JobWorkspaceInterface::class),
            new \stdClass()
        );
    }

    public function testInvoke()
    {
        $job = $this->createMock(JobUnitInterface::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $this->getConductorMock()->expects(self::once())
            ->method('configure')
            ->with($job, $workspace)
            ->willReturnSelf();

        $manager->expects(self::once())
            ->method('updateWorkPlan')
            ->with([ConductorInterface::class => $this->getConductorMock()]);

        self::assertInstanceOf(
            ConfigureConductor::class,
            ($this->buildStep())($job, $workspace, $manager)
        );
    }
}
