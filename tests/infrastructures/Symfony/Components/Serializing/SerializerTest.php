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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\SerializingSerializing;

use PHPUnit\Framework\Attributes\CoversClass;
use Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Serializer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @package Teknoo\Tests\East\Paas\Infrastructures\Symfony\SerializingSerializing
 */
#[CoversClass(Serializer::class)]
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
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $this->getSfSerializerMock()
            ->expects($this->any())
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
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->getSfSerializerMock()
            ->expects($this->any())
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
