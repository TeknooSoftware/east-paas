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
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\Worker;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Http\Message\MessageFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Hook\HookAwareInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Paas\Recipe\Step\Worker\HookBuildContainer;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Recipe\Step\Worker\HookBuildContainer
 */
class HookBuildContainerTest extends TestCase
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

    public function buildStep(): HookBuildContainer
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::any())->method('withAddedHeader')->willReturnSelf();
        $response->expects(self::any())->method('withBody')->willReturnSelf();
        $response->expects(self::any())->method('withStatus')->willReturnSelf();

        $messageFactory = $this->createMock(MessageFactoryInterface::class);
        $messageFactory->expects(self::any())->method('createMessage')->willReturn(
            $response
        );

        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $streamFactory->expects(self::any())->method('createStream')->willReturn(
            $this->createMock(StreamInterface::class)
        );

        return new HookBuildContainer(
            $this->getDispatchHistoryMock(),
            $messageFactory,
            $streamFactory
        );
    }

    public function testInvoke()
    {
        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $compiled = $this->createMock(CompiledDeploymentInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class);
        $client =  $this->createMock(ClientInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $hook1 = $this->createMock(HookInterface::class);
        $hook1->expects(self::once())->method('setPath')->with('foo/bar');
        $hook1->expects(self::once())->method('run')->willReturnCallback(
            function (PromiseInterface $promise) use ($hook1) {
                $promise->success('foo');

                return $hook1;
            }
        );

        $hook2 = new class implements HookInterface, HookAwareInterface {
            public function setContext(JobUnitInterface $jobUnit, JobWorkspaceInterface $workspace): HookAwareInterface {
                return $this;
            }
            public function setPath(string $path): HookInterface {
                return $this;
            }
            public function setOptions(array $options, PromiseInterface $promise): HookInterface {
                return $this;
            }
            public function run(PromiseInterface $promise): HookInterface {
                $promise->success('foo');

                return $this;
            }
        };

        $workspace->expects(self::once())
            ->method('runInRoot')
            ->willReturnCallback(
                function (callable $callback) use ($workspace) {
                    $callback('foo/bar');

                    return $workspace;
                }
            );

        $compiled->expects(self::once())
            ->method('foreachHook')
            ->willReturnCallback(
                function (callable $callback) use ($hook1, $hook2, $compiled) {
                    $callback($hook1);
                    $callback($hook2);

                    return $compiled;
                }
            );

        $project = 'foo';
        $env = 'bar';

        $this->getDispatchHistoryMock()->expects(self::exactly(2))
            ->method('__invoke')
            ->with($project, $env, $jobUnit->getId(), HookBuildContainer::class . ':Result')
            ->willReturnSelf();

        self::assertInstanceOf(
            HookBuildContainer::class,
            ($this->buildStep())(
                $workspace,
                $compiled,
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
        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $compiled = $this->createMock(CompiledDeploymentInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class);
        $client =  $this->createMock(ClientInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $hook1 = $this->createMock(HookInterface::class);
        $hook1->expects(self::once())->method('setPath')->with('foo/bar');
        $hook1->expects(self::once())->method('run')->willReturnCallback(
            function (PromiseInterface $promise) use ($hook1) {
                $promise->fail(new \RuntimeException('foo'));

                return $hook1;
            }
        );
        $hook2 = $this->createMock(HookInterface::class);
        $hook2->expects(self::never())->method('setPath');
        $hook2->expects(self::never())->method('run');

        $workspace->expects(self::once())
            ->method('runInRoot')
            ->willReturnCallback(
                function (callable $callback) use ($workspace) {
                    $callback('foo/bar');

                    return $workspace;
                }
            );

        $compiled->expects(self::once())
            ->method('foreachHook')
            ->willReturnCallback(
                function (callable $callback) use ($hook1, $hook2, $compiled) {
                    $callback($hook1);
                    $callback($hook2);

                    return $compiled;
                }
            );

        $this->getDispatchHistoryMock()->expects(self::never())
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
            HookBuildContainer::class,
            ($this->buildStep())(
                $workspace,
                $compiled,
                'foo',
                'bar',
                $jobUnit,
                $client,
                $manager
            )
        );
    }

    public function testInvokeOnErrorForSecondHook()
    {
        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $compiled = $this->createMock(CompiledDeploymentInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class);
        $client =  $this->createMock(ClientInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $project = 'foo';
        $env = 'bar';

        $hook1 = $this->createMock(HookInterface::class);
        $hook1->expects(self::once())->method('setPath')->with('foo/bar');
        $hook1->expects(self::once())->method('run')->willReturnCallback(
            function (PromiseInterface $promise) use ($hook1) {
                $promise->success('foo');

                return $hook1;
            }
        );
        $hook2 = $this->createMock(HookInterface::class);
        $hook2->expects(self::once())->method('setPath')->with('foo/bar');
        $hook2->expects(self::once())->method('run')->willReturnCallback(
            function (PromiseInterface $promise) use ($hook2) {
                $promise->fail(new \RuntimeException('foo'));

                return $hook2;
            }
        );

        $workspace->expects(self::once())
            ->method('runInRoot')
            ->willReturnCallback(
                function (callable $callback) use ($workspace) {
                    $callback('foo/bar');

                    return $workspace;
                }
            );

        $compiled->expects(self::once())
            ->method('foreachHook')
            ->willReturnCallback(
                function (callable $callback) use ($hook1, $hook2, $compiled) {
                    $callback($hook1);
                    $callback($hook2);

                    return $compiled;
                }
            );

        $this->getDispatchHistoryMock()->expects(self::exactly(1))
            ->method('__invoke')
            ->with($project, $env, $jobUnit->getId(), HookBuildContainer::class . ':Result')
            ->willReturnSelf();

        $manager->expects(self::never())
            ->method('updateWorkPlan');

        $manager->expects(self::once())
            ->method('finish')
            ->willReturnSelf();

        $client->expects(self::once())
            ->method('acceptResponse')
            ->willReturnSelf();

        self::assertInstanceOf(
            HookBuildContainer::class,
            ($this->buildStep())(
                $workspace,
                $compiled,
                $project,
                $env,
                $jobUnit,
                $client,
                $manager
            )
        );
    }
}
