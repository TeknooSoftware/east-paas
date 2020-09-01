<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\Misc;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Paas\Contracts\Serializing\SerializerInterface;
use Teknoo\East\Paas\Recipe\Step\Misc\DisplayError;
use Teknoo\Recipe\ChefInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;

/**
 * @covers \Teknoo\East\Paas\Recipe\Step\Misc\DisplayError
 * @covers \Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait
 * @covers \Teknoo\East\Paas\Recipe\Traits\ResponseTrait
 */
class DisplayErrorTest extends TestCase
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

    public function buildStep(): DisplayError
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

        return new DisplayError(
            $this->getSerializerInterfaceMock(),
            $responseFactory,
            $streamFactory
        );
    }

    public function testInvoke()
    {
        $chef = $this->createMock(ChefInterface::class);
        $client = $this->createMock(ClientInterface::class);

        $error = new \Exception('foo-bar', 123);
        $sError = \json_encode(['message' => 'foo-bar', 'code' => 123]);

        $this->getSerializerInterfaceMock()
            ->expects(self::once())
            ->method('serialize')
            ->with($error, 'json')
            ->willReturnCallback(
                function (
                    $data,
                    string $format,
                    PromiseInterface $promise,
                    array $context = []
                ) use ($sError) {
                    $promise->success($sError);

                    return $this->getSerializerInterfaceMock();
                }
            );;

        $client->expects(self::once())
            ->method('acceptResponse')
            ->with(self::callback(function ($response) {
                return $response instanceof ResponseInterface;
            }));

        $chef->expects(self::once())
            ->method('finish')
            ->with($error);

        self::assertInstanceOf(
            DisplayError::class,
            $this->buildStep()($client, $chef, $error)
        );
    }
}
