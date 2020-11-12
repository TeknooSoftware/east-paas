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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\SerializingSerializing;

use Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Serializer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @package Teknoo\Tests\East\Paas\Infrastructures\Symfony\SerializingSerializing
 */
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
