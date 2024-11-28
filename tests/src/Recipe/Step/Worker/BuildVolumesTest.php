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

namespace Teknoo\Tests\East\Paas\Recipe\Step\Worker;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuilderInterface as VolumeBuilder;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\BuildVolumes;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(BuildVolumes::class)]
class BuildVolumesTest extends TestCase
{
    private ?DispatchHistoryInterface $dispatchHistory = null;

    /**
     * @return DispatchHistoryInterface|MockObject
     */
    public function getDispatchHistoryMock(): DispatchHistoryInterface
    {
        if (!$this->dispatchHistory instanceof DispatchHistoryInterface) {
            $this->dispatchHistory = $this->createMock(DispatchHistoryInterface::class);
        }

        return $this->dispatchHistory;
    }

    public function buildStep(): BuildVolumes
    {
        return new BuildVolumes(
            $this->getDispatchHistoryMock(),
        );
    }

    public function testInvokeBadBuilder()
    {
        $this->expectException(\TypeError::class);

        ($this->buildStep())(
            new \stdClass(),
            $this->createMock(CompiledDeploymentInterface::class),
            $this->createMock(JobWorkspaceInterface::class),
            'foo',
            'bar',
            $this->createMock(JobUnitInterface::class),
            $this->createMock(ClientInterface::class),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadCompiledDeployment()
    {
        $this->expectException(\TypeError::class);

        ($this->buildStep())(
            $this->createMock(VolumeBuilder::class),
            new \stdClass(),
            $this->createMock(JobWorkspaceInterface::class),
            'foo',
            'bar',
            $this->createMock(JobUnitInterface::class),
            $this->createMock(ClientInterface::class),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadJobWorkspace()
    {
        $this->expectException(\TypeError::class);

        ($this->buildStep())(
            $this->createMock(VolumeBuilder::class),
            $this->createMock(CompiledDeploymentInterface::class),
            new \stdClass(),
            'foo',
            'bar',
            $this->createMock(JobUnitInterface::class),
            $this->createMock(ClientInterface::class),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadJobUnit()
    {
        $this->expectException(\TypeError::class);

        ($this->buildStep())(
            $this->createMock(VolumeBuilder::class),
            $this->createMock(CompiledDeploymentInterface::class),
            $this->createMock(JobWorkspaceInterface::class),
            'foo',
            'bar',
            new \stdClass(),
            $this->createMock(ClientInterface::class),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadClient()
    {
        $this->expectException(\TypeError::class);

        ($this->buildStep())(
            $this->createMock(VolumeBuilder::class),
            $this->createMock(CompiledDeploymentInterface::class),
            $this->createMock(JobWorkspaceInterface::class),
            'foo',
            'bar',
            $this->createMock(JobUnitInterface::class),
            new \stdClass(),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadManager()
    {
        $this->expectException(\TypeError::class);

        ($this->buildStep())(
            $this->createMock(VolumeBuilder::class),
            $this->createMock(CompiledDeploymentInterface::class),
            $this->createMock(JobWorkspaceInterface::class),
            'foo',
            'bar',
            $this->createMock(JobUnitInterface::class),
            $this->createMock(ClientInterface::class),
            new \stdClass()
        );
    }

    public function testInvoke()
    {
        $volumeBuilder = $this->createMock(VolumeBuilder::class);
        $compileDep = $this->createMock(CompiledDeploymentInterface::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class);
        $client =  $this->createMock(ClientInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $workspace->expects($this->once())
            ->method('runInRepositoryPath')
            ->willReturnCallback(
                static function (callable $callback) use ($workspace) {
                    $callback('/foo/bar');

                    return $workspace;
                }
            );

        $volumeBuilder->expects($this->once())
            ->method('buildVolumes')
            ->willReturnCallback(
                static function ($compiledDeployment, $root, PromiseInterface $promise) use ($volumeBuilder) {
                    $promise->success('fooBar');

                    return $volumeBuilder;
                }
            );

        $project = 'foo';
        $env = 'bar';

        $this->getDispatchHistoryMock()->expects($this->once())
            ->method('__invoke')
            ->with($project, $env, $jobUnit->getId(), BuildVolumes::class . ':Result')
            ->willReturnSelf();

        self::assertInstanceOf(
            BuildVolumes::class,
            ($this->buildStep())(
                $volumeBuilder,
                $compileDep,
                $workspace,
                $project,
                $env,
                $jobUnit,
                $client,
                $manager
            )
        );
    }

    public function testInvokeOnError()
    {
        $volumeBuilder = $this->createMock(VolumeBuilder::class);
        $compileDep = $this->createMock(CompiledDeploymentInterface::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class);
        $client =  $this->createMock(ClientInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $workspace->expects($this->once())
            ->method('runInRepositoryPath')
            ->willReturnCallback(
                static function (callable $callback) use ($workspace) {
                    $callback('/foo/bar');

                    return $workspace;
                }
            );

        $volumeBuilder->expects($this->once())
            ->method('buildVolumes')
            ->willReturnCallback(
                static function ($compiledDeployment, $root, PromiseInterface $promise) use ($volumeBuilder) {
                    $promise->fail(new \Exception());

                    return $volumeBuilder;
                }
            );

        $this->getDispatchHistoryMock()->expects($this->never())
            ->method('__invoke');

        $manager->expects($this->never())
            ->method('updateWorkPlan');

        $manager->expects($this->once())
            ->method('error');

        self::assertInstanceOf(
            BuildVolumes::class,
            ($this->buildStep())(
                $volumeBuilder,
                $compileDep,
                $workspace,
                'foo',
                'bar',
                $jobUnit,
                $client,
                $manager
            )
        );
    }
}
