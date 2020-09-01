<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\Worker;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Contracts\Container\BuilderInterface as VolumeBuilder;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Recipe\Step\History\SendHistory;
use Teknoo\East\Paas\Recipe\Step\Worker\BuildVolumes;

/**
 * @covers \Teknoo\East\Paas\Recipe\Step\Worker\BuildVolumes
 * @covers \Teknoo\East\Paas\Recipe\Traits\ErrorTrait
 * @covers \Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait
 */
class BuildVolumesTest extends TestCase
{
    private ?SendHistory $sendHistory = null;

    /**
     * @return SendHistory|MockObject
     */
    public function getSendHistoryMock(): SendHistory
    {
        if (!$this->sendHistory instanceof SendHistory) {
            $this->sendHistory = $this->createMock(SendHistory::class);
        }

        return $this->sendHistory;
    }

    public function buildStep(): BuildVolumes
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::any())->method('withAddedHeader')->willReturnSelf();
        $response->expects(self::any())->method('withBody')->willReturnSelf();

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory->expects(self::any())->method('createResponse')->willReturn(
            $response
        );

        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $streamFactory->expects(self::any())->method('createStream')->willReturn(
            $this->createMock(StreamInterface::class)
        );

        return new BuildVolumes(
            $this->getSendHistoryMock(),
            $responseFactory,
            $streamFactory
        );
    }

    public function testInvokeBadBuilder()
    {
        $this->expectException(\TypeError::class);

        ($this->buildStep())(
            new \stdClass(),
            $this->createMock(CompiledDeployment::class),
            $this->createMock(JobWorkspaceInterface::class),
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
            $this->createMock(CompiledDeployment::class),
            new \stdClass(),
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
            $this->createMock(CompiledDeployment::class),
            $this->createMock(JobWorkspaceInterface::class),
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
            $this->createMock(CompiledDeployment::class),
            $this->createMock(JobWorkspaceInterface::class),
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
            $this->createMock(CompiledDeployment::class),
            $this->createMock(JobWorkspaceInterface::class),
            $this->createMock(JobUnitInterface::class),
            $this->createMock(ClientInterface::class),
            new \stdClass()
        );
    }

    public function testInvoke()
    {
        $volumeBuilder = $this->createMock(VolumeBuilder::class);
        $compileDep = $this->createMock(CompiledDeployment::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class);
        $client =  $this->createMock(ClientInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $workspace->expects(self::once())
            ->method('runInRoot')
            ->willReturnCallback(
                static function (callable $callback) use ($workspace) {
                    $callback('/foo/bar');

                    return $workspace;
                }
            );

        $volumeBuilder->expects(self::once())
            ->method('buildVolumes')
            ->willReturnCallback(
                static function ($compiledDeployment, $root, PromiseInterface $promise) use ($volumeBuilder) {
                    $promise->success('fooBar');

                    return $volumeBuilder;
                }
            );

        $this->getSendHistoryMock()->expects(self::once())
            ->method('__invoke')
            ->with($jobUnit, BuildVolumes::class . ':Result')
            ->willReturnSelf();

        self::assertInstanceOf(
            BuildVolumes::class,
            ($this->buildStep())(
                $volumeBuilder,
                $compileDep,
                $workspace,
                $jobUnit,
                $client,
                $manager
            )
        );
    }

    public function testInvokeOnError()
    {
        $volumeBuilder = $this->createMock(VolumeBuilder::class);
        $compileDep = $this->createMock(CompiledDeployment::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class);
        $client =  $this->createMock(ClientInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $workspace->expects(self::once())
            ->method('runInRoot')
            ->willReturnCallback(
                static function (callable $callback) use ($workspace) {
                    $callback('/foo/bar');

                    return $workspace;
                }
            );

        $volumeBuilder->expects(self::once())
            ->method('buildVolumes')
            ->willReturnCallback(
                static function ($compiledDeployment, $root, PromiseInterface $promise) use ($volumeBuilder) {
                    $promise->fail(new \Exception());

                    return $volumeBuilder;
                }
            );

        $this->getSendHistoryMock()->expects(self::never())
            ->method('__invoke');

        $manager->expects(self::never())
            ->method('updateWorkPlan');

        $manager->expects(self::once())
            ->method('finish')
            ->willReturnSelf();

        $client->expects(self::once())
            ->method('acceptResponse')
            ->willReturnSelf();

        self::assertInstanceOf(
            BuildVolumes::class,
            ($this->buildStep())(
                $volumeBuilder,
                $compileDep,
                $workspace,
                $jobUnit,
                $client,
                $manager
            )
        );
    }
}
