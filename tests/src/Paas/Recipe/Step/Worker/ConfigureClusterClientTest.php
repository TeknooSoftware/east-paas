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
use Teknoo\East\Foundation\Http\ClientInterface as EastClient;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Cluster\ClientInterface;
use Teknoo\East\Paas\Cluster\Collection;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureClusterClient;

/**
 * @covers \Teknoo\East\Paas\Recipe\Step\Worker\ConfigureClusterClient
 * @covers \Teknoo\East\Paas\Recipe\Traits\ErrorTrait
 * @covers \Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait
 */
class ConfigureClusterClientTest extends TestCase
{
    /**
     * @var ClientInterface
     */
    private $clients;

    /**
     * @return ClientInterface|MockObject
     */
    public function getClientsMock(): ClientInterface
    {
        if (!$this->clients instanceof ClientInterface) {
            $this->clients = $this->createMock(ClientInterface::class);
        }

        return $this->clients;
    }

    public function buildStep(): ConfigureClusterClient
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

        return new ConfigureClusterClient(
            $this->getClientsMock(),
            $responseFactory,
            $streamFactory
        );
    }

    public function testInvokeBadJob()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(
            new \stdClass(),
            $this->createMock(EastClient::class),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadClient()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(
            $this->createMock(JobUnitInterface::class),
            new \stdClass(),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadManager()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(
            $this->createMock(JobUnitInterface::class),
            $this->createMock(EastClient::class),
            new \stdClass()
        );
    }

    public function testInvoke()
    {
        $job = $this->createMock(JobUnitInterface::class);
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(EastClient::class);

        $manager->expects(self::once())
            ->method('updateWorkPlan')
            ->with([Collection::class => $this->createMock(Collection::class)]);

        $job->expects(self::any())
            ->method('configureCluster')
            ->willReturnCallback(
                function ($client, PromiseInterface $promise) use ($job) {
                    $promise->success($this->createMock(Collection::class));

                    return $job;
                }
            );

        self::assertInstanceOf(
            ConfigureClusterClient::class,
            ($this->buildStep())($job, $client, $manager)
        );
    }

    public function testInvokeOnError()
    {
        $job = $this->createMock(JobUnitInterface::class);
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(EastClient::class);

        $job->expects(self::any())
            ->method('configureCluster')
            ->willReturnCallback(
                function ($client, PromiseInterface $promise) use ($job) {
                    $promise->fail(new \Exception());

                    return $job;
                }
            );

        $manager->expects(self::never())
            ->method('updateWorkPlan');

        $manager->expects(self::once())
            ->method('finish')
            ->willReturnSelf();

        $client->expects(self::once())
            ->method('acceptResponse')
            ->willReturnSelf();

        self::assertInstanceOf(
            ConfigureClusterClient::class,
            ($this->buildStep())($job, $client, $manager)
        );
    }
}
