<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
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
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\History;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Serializing\DeserializerInterface;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Recipe\Step\History\DeserializeHistory;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(DeserializeHistory::class)]
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
            ->expects($this->once())
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

        $manager->expects($this->once())
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
            ->expects($this->once())
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

        $manager->expects($this->never())
            ->method('updateWorkPlan');

        $manager->expects($this->once())
            ->method('error');

        self::assertInstanceOf(
            DeserializeHistory::class,
            ($this->buildStep())('fooBar', $manager, $client)
        );
    }
}
