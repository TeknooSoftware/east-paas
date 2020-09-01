<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\SerializingSerializing;

use Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Deserializer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;

/**
 * @covers \Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Deserializer
 */
class DeserializerTest extends TestCase
{
    private ?SymfonySerializerInterface $serializer = null;

    /**
     * @return SymfonySerializerInterface|MockObject
     */
    private function getSfSerializerMock(): SymfonySerializerInterface
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

    public function testDeserializeWrongData()
    {
        $this->expectException(\TypeError::class);
        $this->buildDeserializer()->deserialize(
            new \stdClass(),
            'foo',
            'bar',
            $this->createMock(PromiseInterface::class),
            []
        );
    }

    public function testDeserializeWrongType()
    {
        $this->expectException(\TypeError::class);
        $this->buildDeserializer()->deserialize(
            'foo',
            new \stdClass(),
            'bar',
            $this->createMock(PromiseInterface::class),
            []
        );
    }

    public function testDeserializeWrongFormat()
    {
        $this->expectException(\TypeError::class);
        $this->buildDeserializer()->deserialize(
            'foo',
            'bar',
            new \stdClass(),
            $this->createMock(PromiseInterface::class),
            []
        );
    }

    public function testDeserializeWrongPromise()
    {
        $this->expectException(\TypeError::class);
        $this->buildDeserializer()->deserialize(
            'foo',
            'bar',
            'foo',
            new \stdClass(),
            []
        );
    }

    public function testDeserializeWrongContext()
    {
        $this->expectException(\TypeError::class);
        $this->buildDeserializer()->deserialize(
            'foo',
            'bar',
            'foo',
            $this->createMock(PromiseInterface::class),
            new \stdClass()
        );
    }

    public function testDeserializeGood()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success');
        $promise->expects(self::never())->method('fail');

        $this->getSfSerializerMock()
            ->expects(self::any())
            ->method('deserialize')
            ->willReturn(['foo' => 'bar']);

        self::assertInstanceOf(
            Deserializer::class,
            $this->buildDeserializer()->deserialize(
                'foo',
                'bar',
                'foo',
                $promise,
                []
            )
        );
    }

    public function testDeserializeFail()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        $this->getSfSerializerMock()
            ->expects(self::any())
            ->method('deserialize')
            ->willThrowException(new \Exception('foo'));

        self::assertInstanceOf(
            Deserializer::class,
            $this->buildDeserializer()->deserialize(
                'foo',
                'bar',
                'foo',
                $promise,
                []
            )
        );
    }
}
