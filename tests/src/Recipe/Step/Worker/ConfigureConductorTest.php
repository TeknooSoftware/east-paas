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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use stdClass;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Compilation\ConductorInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureConductor;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use TypeError;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ConfigureConductor::class)]
class ConfigureConductorTest extends TestCase
{
    private (ConductorInterface&MockObject)|(ConductorInterface&Stub)|null $conductor = null;

    public function getConductorMock(bool $stub = false): (ConductorInterface&Stub)|(ConductorInterface&MockObject)
    {
        if (!$this->conductor instanceof ConductorInterface) {
            if ($stub) {
                $this->conductor = $this->createStub(ConductorInterface::class);
            } else {
                $this->conductor = $this->createMock(ConductorInterface::class);
            }
        }

        return $this->conductor;
    }

    public function buildStep(): ConfigureConductor
    {
        return new ConfigureConductor($this->getConductorMock(true));
    }

    public function testInvokeBadJob(): void
    {
        $this->expectException(TypeError::class);
        ($this->buildStep())(
            new stdClass(),
            $this->createStub(JobWorkspaceInterface::class),
            $this->createStub(ManagerInterface::class)
        );
    }

    public function testInvokeBadWorkspace(): void
    {
        $this->expectException(TypeError::class);
        ($this->buildStep())(
            $this->createStub(JobUnitInterface::class),
            new stdClass(),
            $this->createStub(ManagerInterface::class)
        );
    }

    public function testInvokeBadManager(): void
    {
        $this->expectException(TypeError::class);
        ($this->buildStep())(
            $this->createStub(JobUnitInterface::class),
            $this->createStub(JobWorkspaceInterface::class),
            new stdClass()
        );
    }

    public function testInvoke(): void
    {
        $job = $this->createStub(JobUnitInterface::class);
        $workspace = $this->createStub(JobWorkspaceInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $this->getConductorMock()->expects($this->once())
            ->method('configure')
            ->with($job, $workspace)
            ->willReturnSelf();

        $manager->expects($this->once())
            ->method('updateWorkPlan')
            ->with([ConductorInterface::class => $this->getConductorMock()]);

        $this->assertInstanceOf(ConfigureConductor::class, ($this->buildStep())($job, $workspace, $manager));
    }
}
