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
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\Job;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Serializing\SerializerInterface;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Recipe\Step\Job\SerializeJob;
use Teknoo\Recipe\ChefInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;

/**
 * @covers \Teknoo\East\Paas\Recipe\Step\Job\SerializeJob
 * @covers \Teknoo\East\Paas\Recipe\Traits\ErrorTrait
 * @covers \Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait
 */
class SerializeJobTest extends TestCase
{
    /**
     * @var SerializerInterface
     */
    private $serializerInterface;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SerializerInterface
     */
    public function getSerializerInterfaceMock(): SerializerInterface
    {
        if (!$this->serializerInterface instanceof SerializerInterface) {
            $this->serializerInterface = $this->createMock(SerializerInterface::class);
        }

        return $this->serializerInterface;
    }

    public function buildStep(): SerializeJob
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

        return new SerializeJob(
            $this->getSerializerInterfaceMock(),
            $responseFactory,
            $streamFactory
        );
    }

    public function testInvoke()
    {
        $job = $this->createMock(Job::class);

        $sJob = \json_encode($job);

        $this->getSerializerInterfaceMock()
            ->expects(self::once())
            ->method('serialize')
            ->with($job, 'json')
            ->willReturnCallback(
                function (
                    $data,
                    string $format,
                    PromiseInterface $promise,
                    array $context = []
                ) use ($sJob) {
                    $promise->success($sJob);

                    return $this->getSerializerInterfaceMock();
                }
            );

        $chef = $this->createMock(ManagerInterface::class);
        $chef->expects(self::once())
            ->method('updateWorkPlan')
            ->with(['jobSerialized' => $sJob])
            ->willReturnSelf();

        $client = $this->createMock(ClientInterface::class);

        self::assertInstanceOf(
            SerializeJob::class,
            $this->buildStep()($job, $chef, $client)
        );
    }

    public function testInvokeOnError()
    {
        $job = $this->createMock(Job::class);

        $this->getSerializerInterfaceMock()
            ->expects(self::once())
            ->method('serialize')
            ->with($job, 'json')
            ->willReturnCallback(
                function (
                    $data,
                    string $format,
                    PromiseInterface $promise,
                    array $context = []
                ) {
                    $promise->fail(new \Exception());

                    return $this->getSerializerInterfaceMock();
                }
            );

        $chef = $this->createMock(ManagerInterface::class);
        $chef->expects(self::never())->method('updateWorkPlan');
        $chef->expects(self::once())->method('finish');

        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('acceptResponse');


        self::assertInstanceOf(
            SerializeJob::class,
            $this->buildStep()($job, $chef, $client)
        );
    }
}
