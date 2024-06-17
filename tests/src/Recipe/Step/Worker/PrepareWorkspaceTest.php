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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\Worker;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\PrepareWorkspace;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(PrepareWorkspace::class)]
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

        $this->getWorkspaceMock()->expects($this->once())
            ->method('setJob')
            ->with($job)
            ->willReturnSelf();

        $manager->expects($this->once())
            ->method('updateWorkPlan')
            ->with([JobWorkspaceInterface::class => $this->getWorkspaceMock()]);

        self::assertInstanceOf(
            PrepareWorkspace::class,
            ($this->buildStep())($job, $manager)
        );
    }
}
