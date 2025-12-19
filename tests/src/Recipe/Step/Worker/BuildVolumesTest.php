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

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use stdClass;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuilderInterface as VolumeBuilder;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\BuildVolumes;
use TypeError;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(BuildVolumes::class)]
class BuildVolumesTest extends TestCase
{
    private (DispatchHistoryInterface&MockObject)|(DispatchHistoryInterface&Stub)|null $dispatchHistory = null;

    public function getDispatchHistoryMock(bool $stub = false): (DispatchHistoryInterface&Stub)|(DispatchHistoryInterface&MockObject)
    {
        if (!$this->dispatchHistory instanceof DispatchHistoryInterface) {
            if ($stub) {
                $this->dispatchHistory = $this->createStub(DispatchHistoryInterface::class);
            } else {
                $this->dispatchHistory = $this->createMock(DispatchHistoryInterface::class);
            }
        }

        return $this->dispatchHistory;
    }

    public function buildStep(): BuildVolumes
    {
        return new BuildVolumes(
            $this->getDispatchHistoryMock(true),
        );
    }

    public function testInvokeBadBuilder(): void
    {
        $this->expectException(TypeError::class);

        ($this->buildStep())(
            new stdClass(),
            $this->createStub(CompiledDeploymentInterface::class),
            $this->createStub(JobWorkspaceInterface::class),
            'foo',
            'bar',
            $this->createStub(JobUnitInterface::class),
            $this->createStub(ClientInterface::class),
            $this->createStub(ManagerInterface::class)
        );
    }

    public function testInvokeBadCompiledDeployment(): void
    {
        $this->expectException(TypeError::class);

        ($this->buildStep())(
            $this->createStub(VolumeBuilder::class),
            new stdClass(),
            $this->createStub(JobWorkspaceInterface::class),
            'foo',
            'bar',
            $this->createStub(JobUnitInterface::class),
            $this->createStub(ClientInterface::class),
            $this->createStub(ManagerInterface::class)
        );
    }

    public function testInvokeBadJobWorkspace(): void
    {
        $this->expectException(TypeError::class);

        ($this->buildStep())(
            $this->createStub(VolumeBuilder::class),
            $this->createStub(CompiledDeploymentInterface::class),
            new stdClass(),
            'foo',
            'bar',
            $this->createStub(JobUnitInterface::class),
            $this->createStub(ClientInterface::class),
            $this->createStub(ManagerInterface::class)
        );
    }

    public function testInvokeBadJobUnit(): void
    {
        $this->expectException(TypeError::class);

        ($this->buildStep())(
            $this->createStub(VolumeBuilder::class),
            $this->createStub(CompiledDeploymentInterface::class),
            $this->createStub(JobWorkspaceInterface::class),
            'foo',
            'bar',
            new stdClass(),
            $this->createStub(ClientInterface::class),
            $this->createStub(ManagerInterface::class)
        );
    }

    public function testInvokeBadClient(): void
    {
        $this->expectException(TypeError::class);

        ($this->buildStep())(
            $this->createStub(VolumeBuilder::class),
            $this->createStub(CompiledDeploymentInterface::class),
            $this->createStub(JobWorkspaceInterface::class),
            'foo',
            'bar',
            $this->createStub(JobUnitInterface::class),
            new stdClass(),
            $this->createStub(ManagerInterface::class)
        );
    }

    public function testInvokeBadManager(): void
    {
        $this->expectException(TypeError::class);

        ($this->buildStep())(
            $this->createStub(VolumeBuilder::class),
            $this->createStub(CompiledDeploymentInterface::class),
            $this->createStub(JobWorkspaceInterface::class),
            'foo',
            'bar',
            $this->createStub(JobUnitInterface::class),
            $this->createStub(ClientInterface::class),
            new stdClass()
        );
    }

    public function testInvoke(): void
    {
        $volumeBuilder = $this->createMock(VolumeBuilder::class);
        $compileDep = $this->createStub(CompiledDeploymentInterface::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createStub(JobUnitInterface::class);
        $client =  $this->createStub(ClientInterface::class);
        $manager = $this->createStub(ManagerInterface::class);

        $workspace->expects($this->once())
            ->method('runInRepositoryPath')
            ->willReturnCallback(
                static function (callable $callback) use ($workspace): MockObject|Stub {
                    $callback('/foo/bar');

                    return $workspace;
                }
            );

        $volumeBuilder->expects($this->once())
            ->method('buildVolumes')
            ->willReturnCallback(
                static function ($compiledDeployment, $root, PromiseInterface $promise) use ($volumeBuilder): MockObject|Stub {
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

        $this->assertInstanceOf(BuildVolumes::class, ($this->buildStep())(
            $volumeBuilder,
            $compileDep,
            $workspace,
            $project,
            $env,
            $jobUnit,
            $client,
            $manager
        ));
    }

    public function testInvokeOnError(): void
    {
        $volumeBuilder = $this->createMock(VolumeBuilder::class);
        $compileDep = $this->createStub(CompiledDeploymentInterface::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createStub(JobUnitInterface::class);
        $client =  $this->createStub(ClientInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $workspace->expects($this->once())
            ->method('runInRepositoryPath')
            ->willReturnCallback(
                static function (callable $callback) use ($workspace): MockObject|Stub {
                    $callback('/foo/bar');

                    return $workspace;
                }
            );

        $volumeBuilder->expects($this->once())
            ->method('buildVolumes')
            ->willReturnCallback(
                static function ($compiledDeployment, $root, PromiseInterface $promise) use ($volumeBuilder): MockObject|Stub {
                    $promise->fail(new Exception());

                    return $volumeBuilder;
                }
            );

        $this->getDispatchHistoryMock()->expects($this->never())
            ->method('__invoke');

        $manager->expects($this->never())
            ->method('updateWorkPlan');

        $manager->expects($this->once())
            ->method('error');

        $this->assertInstanceOf(BuildVolumes::class, ($this->buildStep())(
            $volumeBuilder,
            $compileDep,
            $workspace,
            'foo',
            'bar',
            $jobUnit,
            $client,
            $manager
        ));
    }
}
