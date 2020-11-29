<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
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
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Teknoo\East\Foundation\Http\ClientInterface as EastClient;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Cluster\ClientInterface;
use Teknoo\East\Paas\Cluster\Collection;
use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\Exposing;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Recipe\Step\Worker\Exposing
 * @covers \Teknoo\East\Paas\Recipe\Traits\ErrorTrait
 * @covers \Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait
 */
class ExposingTest extends TestCase
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

    public function buildStep(): Exposing
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

        return new Exposing(
            $this->getDispatchHistoryMock(),
            $responseFactory,
            $streamFactory
        );
    }

    public function testInvokeBadCollection()
    {
        $this->expectException(\TypeError::class);

        ($this->buildStep())(
            new \stdClass(),
            $this->createMock(CompiledDeployment::class),
            $this->createMock(EastClient::class),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadCompiledExposment()
    {
        $this->expectException(\TypeError::class);

        ($this->buildStep())(
            $this->createMock(Collection::class),
            new \stdClass(),
            $this->createMock(EastClient::class),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadClient()
    {
        $this->expectException(\TypeError::class);

        ($this->buildStep())(
            $this->createMock(Collection::class),
            $this->createMock(CompiledDeployment::class),
            new \stdClass(),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadManager()
    {
        $this->expectException(\TypeError::class);

        ($this->buildStep())(
            $this->createMock(Collection::class),
            $this->createMock(CompiledDeployment::class),
            $this->createMock(EastClient::class),
            new \stdClass()
        );
    }

    public function testInvoke()
    {
        $compileDep = $this->createMock(CompiledDeployment::class);
        $collection = $this->createMock(Collection::class);
        $jobUnit = $this->createMock(JobUnitInterface::class);
        $eastClient =  $this->createMock(EastClient::class);
        $manager = $this->createMock(ManagerInterface::class);

        $client = $this->createMock(ClientInterface::class);
        $collection->expects(self::any())
            ->method('getIterator')
            ->willReturnCallback(function () use ($client) {
                yield $client;
            });

        $client->expects(self::any())
            ->method('expose')
            ->willReturnCallback(
                static function ($compiledExposment, PromiseInterface $promise) use ($client) {
                    $promise->success(['foo' => 'bar']);

                    return $client;
                }
            );

        $this->getDispatchHistoryMock()->expects(self::once())
            ->method('__invoke')
            ->with($jobUnit, Exposing::class . ':Result')
            ->willReturnSelf();

        self::assertInstanceOf(
            Exposing::class,
            ($this->buildStep())(
                $collection,
                $compileDep,
                $eastClient,
                $manager,
                $jobUnit
            )
        );
    }

    public function testInvokeOnError()
    {
        $compileDep = $this->createMock(CompiledDeployment::class);
        $collection = $this->createMock(Collection::class);
        $eastClient =  $this->createMock(EastClient::class);
        $manager = $this->createMock(ManagerInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class);

        $client = $this->createMock(ClientInterface::class);
        $collection->expects(self::any())
            ->method('getIterator')
            ->willReturnCallback(function () use ($client) {
                yield $client;
            });

        $client->expects(self::any())
            ->method('expose')
            ->willReturnCallback(
                static function ($compiledExposment, PromiseInterface $promise) use ($client) {
                    $promise->fail(new \Exception());

                    return $client;
                }
            );

        $this->getDispatchHistoryMock()->expects(self::never())
            ->method('__invoke');

        $manager->expects(self::never())
            ->method('updateWorkPlan');

        $manager->expects(self::once())
            ->method('finish')
            ->willReturnSelf();

        $eastClient->expects(self::once())
            ->method('acceptResponse')
            ->willReturnSelf();

        self::assertInstanceOf(
            Exposing::class,
            ($this->buildStep())(
                $collection,
                $compileDep,
                $eastClient,
                $manager,
                $jobUnit
            )
        );
    }
}
