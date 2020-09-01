<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\SerializingSerializing;

use Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Serializer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;

class SerializerTest extends TestCase
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

    /**
     * @covers \Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Serializer
     */
    public function buildSerializer(): Serializer
    {
        return new Serializer(
            $this->getSfSerializerMock()
        );
    }

    public function testSerializeWrongPromise()
    {
        $this->expectException(\TypeError::class);
        $this->buildSerializer()->serialize(
            new \stdClass(),
            'foo',
            new \stdClass(),
            []
        );
    }

    public function testSerializeWrongFormat()
    {
        $this->expectException(\TypeError::class);
        $this->buildSerializer()->serialize(
            new \stdClass(),
            new \stdClass(),
            $this->createMock(PromiseInterface::class),
            []
        );
    }

    public function testSerializeWrongContext()
    {
        $this->expectException(\TypeError::class);
        $this->buildSerializer()->serialize(
            new \stdClass(),
            'foo',
            $this->createMock(PromiseInterface::class),
            new \stdClass()
        );
    }

    public function testSerializeGood()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success');
        $promise->expects(self::never())->method('fail');

        $this->getSfSerializerMock()
            ->expects(self::any())
            ->method('serialize')
            ->willReturn('foo');

        self::assertInstanceOf(
            Serializer::class,
            $this->buildSerializer()->serialize(
                new \stdClass(),
                'foo',
                $promise,
                []
            )
        );
    }

    public function testSerializeFail()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        $this->getSfSerializerMock()
            ->expects(self::any())
            ->method('serialize')
            ->willThrowException(new \Exception('foo'));

        self::assertInstanceOf(
            Serializer::class,
            $this->buildSerializer()->serialize(
                new \stdClass(),
                'foo',
                $promise,
                []
            )
        );
    }
}
