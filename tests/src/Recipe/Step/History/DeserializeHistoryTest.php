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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\History;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Serializing\DeserializerInterface;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Recipe\Step\History\DeserializeHistory;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Recipe\Step\History\DeserializeHistory
 */
class DeserializeHistoryTest extends TestCase
{
    /**
     * @var DeserializerInterface
     */
    private $deserializer;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DeserializerInterface
     */
    public function getDeserializer(): DeserializerInterface
    {
        if (!$this->deserializer instanceof DeserializerInterface) {
            $this->deserializer = $this->createMock(DeserializerInterface::class);
        }

        return $this->deserializer;
    }

    public function buildStep(): DeserializeHistory
    {
        return new DeserializeHistory(
            $this->getDeserializer(),
        );
    }

    public function testInvokeBadSerializedHistory()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(
            new \stdClass(),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadManager()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(
            'fooBar',
            new \stdClass()
        );
    }

    public function testInvoke()
    {
        $history = $this->createMock(History::class);
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(ClientInterface::class);

        $this->getDeserializer()
            ->expects(self::once())
            ->method('deserialize')
            ->with('fooBar', History::class, 'json')
            ->willReturnCallback(
                function (
                    string $data,
                    string $type,
                    string $format,
                    PromiseInterface $promise,
                    array $context = []
                ) use ($history) {
                    $promise->success($history);

                    return $this->getDeserializer();
                }
            );

        $manager->expects(self::once())
            ->method('updateWorkPlan')
            ->with([History::class => $history])
            ->willReturnSelf();

        self::assertInstanceOf(
            DeserializeHistory::class,
            ($this->buildStep())('fooBar', $manager, $client)
        );
    }

    public function testInvokeErrorInDeserialization()
    {
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(ClientInterface::class);

        $error = new \Exception('fooBar');
        $this->getDeserializer()
            ->expects(self::once())
            ->method('deserialize')
            ->with('fooBar', History::class, 'json')
            ->willReturnCallback(
                function (
                    string $data,
                    string $type,
                    string $format,
                    PromiseInterface $promise,
                    array $context = []
                ) use ($error) {
                    $promise->fail($error);

                    return $this->getDeserializer();
                }
            );

        $manager->expects(self::never())
            ->method('updateWorkPlan');

        $this->expectException(\RuntimeException::class);

        self::assertInstanceOf(
            DeserializeHistory::class,
            ($this->buildStep())('fooBar', $manager, $client)
        );
    }
}
