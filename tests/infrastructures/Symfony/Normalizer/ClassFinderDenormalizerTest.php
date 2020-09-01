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
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\SerializingNormalier;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\ClassFinderDenormalizer;
use Teknoo\East\Paas\Object\Environment;

/**
 * @covers \Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\ClassFinderDenormalizer
 */
class ClassFinderDenormalizerTest extends TestCase
{
    public function buildNormalizer(): ClassFinderDenormalizer
    {
        return new ClassFinderDenormalizer();
    }

    public function testSetDenormalizerBadArgument()
    {
        $this->expectException(\TypeError::class);
        $this->buildNormalizer()->setDenormalizer(new \stdClass());
    }

    public function testSetDenormalizer()
    {
        self::assertInstanceOf(
            ClassFinderDenormalizer::class,
            $this->buildNormalizer()->setDenormalizer(
                $this->createMock(DenormalizerInterface::class)
            )
        );
    }

    public function testSupportsDenormalization()
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        self::assertFalse($this->buildNormalizer()->supportsDenormalization(new \stdClass(), 'foo'));
        self::assertFalse($this->buildNormalizer()->supportsDenormalization(['foo'=>'bar'], 'foo'));
        self::assertFalse($this->buildNormalizer()->supportsDenormalization(['@class'=>Environment::class], 'foo'));
        self::assertFalse($this->buildNormalizer()->setDenormalizer($denormalizer)->supportsDenormalization(new \stdClass(), 'foo'));
        self::assertFalse($this->buildNormalizer()->setDenormalizer($denormalizer)->supportsDenormalization(['foo'=>'bar'], 'foo'));
        self::assertTrue($this->buildNormalizer()->setDenormalizer($denormalizer)->supportsDenormalization(['@class'=>Environment::class], 'foo'));
    }

    public function testDenormalizeNotDenormalizer()
    {
        $this->expectException(\RuntimeException::class);
        $this->buildNormalizer()->denormalize(new \stdClass(), 'foo');
    }

    public function testDenormalizeNotArray()
    {
        $this->expectException(\RuntimeException::class);
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $this->buildNormalizer()->setDenormalizer($denormalizer)->denormalize(new \stdClass(), 'foo');
    }

    public function testDenormalizeNotClass()
    {
        $this->expectException(\RuntimeException::class);
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $this->buildNormalizer()->setDenormalizer($denormalizer)->denormalize(['foo' => 'bar'], 'foo');
    }

    public function testDenormalize()
    {
        $env = new Environment();

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects(self::once())
            ->method('denormalize')
            ->with(['name'=>'foo'], Environment::class)
            ->willReturn($env);

        self::assertEquals(
            $env,
            $this->buildNormalizer()->setDenormalizer($denormalizer)
                ->denormalize(['@class' => Environment::class, 'name'=>'foo'], 'foo')
        );
    }
}
