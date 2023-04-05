<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\Worker;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuilderInterface as ImageBuilder;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\BuildImages;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Recipe\Step\Worker\BuildImages
 */
class BuildImagesTest extends TestCase
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

    public function buildStep(): BuildImages
    {
        return new BuildImages(
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
            $this->createMock(ImageBuilder::class),
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
            $this->createMock(ImageBuilder::class),
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
            $this->createMock(ImageBuilder::class),
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
            $this->createMock(ImageBuilder::class),
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
            $this->createMock(ImageBuilder::class),
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
        $imageBuilder = $this->createMock(ImageBuilder::class);
        $compileDep = $this->createMock(CompiledDeploymentInterface::class);
        $jobWorkspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class);
        $client =  $this->createMock(ClientInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $imageBuilder->expects(self::once())
            ->method('buildImages')
            ->willReturnCallback(
                static function ($compiledDeployment, $root, PromiseInterface $promise) use ($imageBuilder) {
                    $promise->success('fooBar');

                    return $imageBuilder;
                }
            );

        $jobWorkspace->expects(self::once())
            ->method('runInRepositoryPath')
            ->willReturnCallback(
                static function (callable $callback) use ($jobWorkspace) {
                    $callback('/foo/bar');

                    return $jobWorkspace;
                }
            );

        $project = 'foo';
        $env = 'bar';

        $this->getDispatchHistoryMock()->expects(self::once())
            ->method('__invoke')
            ->with($project, $env, $jobUnit->getId(), BuildImages::class . ':Result')
            ->willReturnSelf();

        self::assertInstanceOf(
            BuildImages::class,
            ($this->buildStep())(
                $imageBuilder,
                $compileDep,
                $jobWorkspace,
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
        $imageBuilder = $this->createMock(ImageBuilder::class);
        $compileDep = $this->createMock(CompiledDeploymentInterface::class);
        $jobWorkspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class);
        $client =  $this->createMock(ClientInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $imageBuilder->expects(self::once())
            ->method('buildImages')
            ->willReturnCallback(
                static function ($compiledDeployment, $root, PromiseInterface $promise) use ($imageBuilder) {
                    $promise->fail(new \Exception());

                    return $imageBuilder;
                }
            );

        $jobWorkspace->expects(self::once())
            ->method('runInRepositoryPath')
            ->willReturnCallback(
                static function (callable $callback) use ($jobWorkspace) {
                    $callback('/foo/bar');

                    return $jobWorkspace;
                }
            );

        $this->getDispatchHistoryMock()->expects(self::never())
            ->method('__invoke');

        $manager->expects(self::never())
            ->method('updateWorkPlan');

        $manager->expects(self::once())
            ->method('error');

        self::assertInstanceOf(
            BuildImages::class,
            ($this->buildStep())(
                $imageBuilder,
                $compileDep,
                $jobWorkspace,
                'foo',
                'bar',
                $jobUnit,
                $client,
                $manager
            )
        );
    }
}
