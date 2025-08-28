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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Serializing;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Deserializer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use TypeError;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Deserializer::class)]
class DeserializerTest extends TestCase
{
    private (SymfonySerializerInterface&MockObject)|null $serializer = null;

    private function getSfSerializerMock(): SymfonySerializerInterface&MockObject
    {
        if (!$this->serializer instanceof SymfonySerializerInterface) {
            $this->serializer = $this->createMock(SymfonySerializerInterface::class);
        }

        return $this->serializer;
    }

    public function buildDeserializer(): Deserializer
    {
        return new Deserializer(
            $this->getSfSerializerMock()
        );
    }

    public function testDeserializeWrongData(): void
    {
        $this->expectException(TypeError::class);
        $this->buildDeserializer()->deserialize(
            new stdClass(),
            'foo',
            'bar',
            $this->createMock(PromiseInterface::class),
            []
        );
    }

    public function testDeserializeWrongType(): void
    {
        $this->expectException(TypeError::class);
        $this->buildDeserializer()->deserialize(
            'foo',
            new stdClass(),
            'bar',
            $this->createMock(PromiseInterface::class),
            []
        );
    }

    public function testDeserializeWrongFormat(): void
    {
        $this->expectException(TypeError::class);
        $this->buildDeserializer()->deserialize(
            'foo',
            'bar',
            new stdClass(),
            $this->createMock(PromiseInterface::class),
            []
        );
    }

    public function testDeserializeWrongPromise(): void
    {
        $this->expectException(TypeError::class);
        $this->buildDeserializer()->deserialize(
            'foo',
            'bar',
            'foo',
            new stdClass(),
            []
        );
    }

    public function testDeserializeWrongContext(): void
    {
        $this->expectException(TypeError::class);
        $this->buildDeserializer()->deserialize(
            'foo',
            'bar',
            'foo',
            $this->createMock(PromiseInterface::class),
            new stdClass()
        );
    }

    public function testDeserializeGood(): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $this->getSfSerializerMock()
            ->method('deserialize')
            ->willReturn(['foo' => 'bar']);

        $this->assertInstanceOf(Deserializer::class, $this->buildDeserializer()->deserialize(
            'foo',
            'bar',
            'foo',
            $promise,
            []
        ));
    }

    public function testDeserializeFail(): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->getSfSerializerMock()
            ->method('deserialize')
            ->willThrowException(new Exception('foo'));

        $this->assertInstanceOf(Deserializer::class, $this->buildDeserializer()->deserialize(
            'foo',
            'bar',
            'foo',
            $promise,
            []
        ));
    }
}
