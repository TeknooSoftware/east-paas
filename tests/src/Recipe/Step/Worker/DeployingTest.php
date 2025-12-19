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
use Teknoo\East\Foundation\Client\ClientInterface as EastClient;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface;
use Teknoo\East\Paas\Cluster\Collection;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\Deploying;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Deploying::class)]
class DeployingTest extends TestCase
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

    public function buildStep(): Deploying
    {
        return new Deploying(
            $this->getDispatchHistoryMock(true),
        );
    }

    public function testInvoke(): void
    {
        $compileDep = $this->createStub(CompiledDeploymentInterface::class);
        $collection = $this->createStub(Collection::class);
        $jobUnit = $this->createStub(JobUnitInterface::class);
        $eastClient =  $this->createStub(EastClient::class);
        $manager = $this->createStub(ManagerInterface::class);

        $client = $this->createStub(DriverInterface::class);
        $collection
            ->method('getIterator')
            ->willReturnCallback(function () use ($client): \Traversable {
                yield $client;
            });

        $client
            ->method('deploy')
            ->willReturnCallback(
                static function (CompiledDeploymentInterface $compiledDeployment, PromiseInterface $promise) use ($client): MockObject|Stub {
                    $promise->success(['foo' => 'bar']);

                    return $client;
                }
            );

        $project = 'foo';
        $env = 'bar';

        $this->getDispatchHistoryMock()->expects($this->once())
            ->method('__invoke')
            ->with($project, $env, $jobUnit->getId(), Deploying::class . ':Result')
            ->willReturnSelf();

        $this->assertInstanceOf(Deploying::class, ($this->buildStep())(
            $collection,
            $compileDep,
            $eastClient,
            $manager,
            $project,
            $env,
            $jobUnit
        ));
    }

    public function testInvokeOnError(): void
    {
        $compileDep = $this->createStub(CompiledDeploymentInterface::class);
        $collection = $this->createStub(Collection::class);
        $eastClient =  $this->createStub(EastClient::class);
        $manager = $this->createMock(ManagerInterface::class);
        $jobUnit = $this->createStub(JobUnitInterface::class);

        $client = $this->createStub(DriverInterface::class);
        $collection
            ->method('getIterator')
            ->willReturnCallback(function () use ($client): \Traversable {
                yield $client;
            });

        $client
            ->method('deploy')
            ->willReturnCallback(
                static function (CompiledDeploymentInterface $compiledDeployment, PromiseInterface $promise) use ($client): MockObject|Stub {
                    $promise->fail(new \Exception());

                    return $client;
                }
            );

        $this->getDispatchHistoryMock()->expects($this->never())
            ->method('__invoke');

        $manager->expects($this->never())
            ->method('updateWorkPlan');

        $manager->expects($this->once())
            ->method('error');

        $this->assertInstanceOf(Deploying::class, ($this->buildStep())(
            $collection,
            $compileDep,
            $eastClient,
            $manager,
            'foo',
            'bar',
            $jobUnit
        ));
    }
}
