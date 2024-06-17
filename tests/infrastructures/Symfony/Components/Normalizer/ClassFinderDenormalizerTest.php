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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\SerializingNormalier;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\ClassFinderDenormalizer;
use Teknoo\East\Paas\Object\Environment;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ClassFinderDenormalizer::class)]
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
        $this->buildNormalizer()->setDenormalizer(
            $this->createMock(DenormalizerInterface::class)
        );
        self::assertTrue(true);
    }

    public function testSupportsDenormalization()
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        self::assertFalse($this->buildNormalizer()->supportsDenormalization(new \stdClass(), 'foo'));
        self::assertFalse($this->buildNormalizer()->supportsDenormalization(['foo'=>'bar'], 'foo'));
        self::assertFalse($this->buildNormalizer()->supportsDenormalization(['@class'=>Environment::class], 'foo'));
        $n = $this->buildNormalizer();
        $n->setDenormalizer($denormalizer);
        self::assertFalse($n->supportsDenormalization(new \stdClass(), 'foo'));
        $n = $this->buildNormalizer();
        $n->setDenormalizer($denormalizer);
        self::assertFalse($n->supportsDenormalization(['foo'=>'bar'], 'foo'));
        $n = $this->buildNormalizer();
        $n->setDenormalizer($denormalizer);
        self::assertTrue($n->supportsDenormalization(['@class'=>Environment::class], 'foo'));
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
        $n = $this->buildNormalizer();
        $n->setDenormalizer($denormalizer);
        $n->denormalize(new \stdClass(), 'foo');
    }

    public function testDenormalizeNotClass()
    {
        $this->expectException(\RuntimeException::class);
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $n = $this->buildNormalizer();
        $n->setDenormalizer($denormalizer);
        $n->denormalize(['foo' => 'bar'], 'foo');
    }

    public function testDenormalize()
    {
        $env = new Environment();

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects($this->once())
            ->method('denormalize')
            ->with(['name'=>'foo'], Environment::class)
            ->willReturn($env);

        $n = $this->buildNormalizer();
        $n->setDenormalizer($denormalizer);
        self::assertEquals(
            $env,
            $n->denormalize(['@class' => Environment::class, 'name'=>'foo'], 'foo')
        );
    }

    public function testGetSupportedTypes()
    {
        self::assertIsArray($this->buildNormalizer()->getSupportedTypes('array'));
    }
}
