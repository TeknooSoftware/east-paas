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
use PHPUnit\Framework\TestCase;
use stdClass;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\PrepareWorkspace;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use TypeError;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(PrepareWorkspace::class)]
class PrepareWorkspaceTest extends TestCase
{
    private (JobWorkspaceInterface&MockObject)|null $workspace = null;

    public function getWorkspaceMock(): JobWorkspaceInterface&MockObject
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

    public function testInvokeBadJob(): void
    {
        $this->expectException(TypeError::class);
        ($this->buildStep())(
            new stdClass(),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadManager(): void
    {
        $this->expectException(TypeError::class);
        ($this->buildStep())(
            $this->createMock(JobUnitInterface::class),
            new stdClass()
        );
    }

    public function testInvoke(): void
    {
        $job = $this->createMock(JobUnitInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $this->getWorkspaceMock()->expects($this->once())
            ->method('setJob')
            ->with($job)
            ->willReturnSelf();

        $manager->expects($this->once())
            ->method('updateWorkPlan')
            ->with([JobWorkspaceInterface::class => $this->getWorkspaceMock()]);

        $this->assertInstanceOf(
            PrepareWorkspace::class,
            ($this->buildStep())($job, $manager)
        );
    }
}
