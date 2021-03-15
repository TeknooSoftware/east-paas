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

namespace Teknoo\Tests\East\Paas\Recipe\Step\History;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Http\Message\MessageFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Serializing\SerializerInterface;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Recipe\Step\History\SerializeHistory;
use Teknoo\Recipe\ChefInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Recipe\Step\History\SerializeHistory
 * @covers \Teknoo\East\Paas\Recipe\Traits\ErrorTrait
 * @covers \Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait
 */
class SerializeHistoryTest extends TestCase
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

    public function buildStep(): SerializeHistory
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

        return new SerializeHistory(
            $this->getSerializerInterfaceMock(),
            $messageFactory,
            $streamFactory
        );
    }

    public function testInvoke()
    {
        $history = $this->createMock(History::class);

        $sHistory = \json_encode($history);

        $this->getSerializerInterfaceMock()
            ->expects(self::once())
            ->method('serialize')
            ->with($history, 'json')
            ->willReturnCallback(
                function (
                    $data,
                    string $format,
                    PromiseInterface $promise,
                    array $context = []
                ) use ($sHistory) {
                    $promise->success($sHistory);

                    return $this->getSerializerInterfaceMock();
                }
            );

        $chef = $this->createMock(ManagerInterface::class);
        $chef->expects(self::once())
            ->method('updateWorkPlan')
            ->with(['historySerialized' => $sHistory])
            ->willReturnSelf();

        $client = $this->createMock(ClientInterface::class);

        self::assertInstanceOf(
            SerializeHistory::class,
            $this->buildStep()($history, $chef, $client)
        );
    }

    public function testInvokeError()
    {
        $history = $this->createMock(History::class);

        $this->getSerializerInterfaceMock()
            ->expects(self::once())
            ->method('serialize')
            ->with($history, 'json')
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
            SerializeHistory::class,
            $this->buildStep()($history, $chef, $client)
        );
    }
}
