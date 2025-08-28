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

namespace Teknoo\Tests\East\Paas\Recipe\Step\History;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Serializing\DeserializerInterface;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Recipe\Step\History\DeserializeHistory;
use Teknoo\Recipe\Promise\PromiseInterface;
use TypeError;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(DeserializeHistory::class)]
class DeserializeHistoryTest extends TestCase
{
    private (DeserializerInterface&MockObject)|null $deserializer = null;

    public function getDeserializer(): DeserializerInterface&MockObject
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

    public function testInvokeBadSerializedHistory(): void
    {
        $this->expectException(TypeError::class);
        ($this->buildStep())(
            new stdClass(),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadManager(): void
    {
        $this->expectException(TypeError::class);
        ($this->buildStep())(
            'fooBar',
            new stdClass()
        );
    }

    public function testInvoke(): void
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
                ) use ($history): DeserializerInterface&MockObject {
                    $promise->success($history);

                    return $this->getDeserializer();
                }
            );

        $manager->expects($this->once())
            ->method('updateWorkPlan')
            ->with([History::class => $history])
            ->willReturnSelf();

        $this->assertInstanceOf(
            DeserializeHistory::class,
            ($this->buildStep())('fooBar', $manager, $client)
        );
    }

    public function testInvokeErrorInDeserialization(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(ClientInterface::class);

        $error = new Exception('fooBar');
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
                ) use ($error): DeserializerInterface&MockObject {
                    $promise->fail($error);

                    return $this->getDeserializer();
                }
            );

        $manager->expects($this->never())
            ->method('updateWorkPlan');

        $manager->expects($this->once())
            ->method('error');

        $this->assertInstanceOf(
            DeserializeHistory::class,
            ($this->buildStep())('fooBar', $manager, $client)
        );
    }
}
