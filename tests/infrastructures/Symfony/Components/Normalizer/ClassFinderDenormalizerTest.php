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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Normalizer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\ClassFinderDenormalizer;
use Teknoo\East\Paas\Object\Environment;
use TypeError;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ClassFinderDenormalizer::class)]
class ClassFinderDenormalizerTest extends TestCase
{
    public function buildNormalizer(): ClassFinderDenormalizer
    {
        return new ClassFinderDenormalizer();
    }

    public function testSetDenormalizerBadArgument(): void
    {
        $this->expectException(TypeError::class);
        $this->buildNormalizer()->setDenormalizer(new stdClass());
    }

    public function testSetDenormalizer(): void
    {
        $this->buildNormalizer()->setDenormalizer(
            $this->createMock(DenormalizerInterface::class)
        );
        $this->assertTrue(true);
    }

    public function testSupportsDenormalization(): void
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $this->assertFalse($this->buildNormalizer()->supportsDenormalization(new stdClass(), 'foo'));
        $this->assertFalse($this->buildNormalizer()->supportsDenormalization(['foo' => 'bar'], 'foo'));
        $this->assertFalse($this->buildNormalizer()->supportsDenormalization(['@class' => Environment::class], 'foo'));
        $n = $this->buildNormalizer();
        $n->setDenormalizer($denormalizer);
        $this->assertFalse($n->supportsDenormalization(new stdClass(), 'foo'));
        $n = $this->buildNormalizer();
        $n->setDenormalizer($denormalizer);
        $this->assertFalse($n->supportsDenormalization(['foo' => 'bar'], 'foo'));
        $n = $this->buildNormalizer();
        $n->setDenormalizer($denormalizer);
        $this->assertTrue($n->supportsDenormalization(['@class' => Environment::class], 'foo'));
    }

    public function testDenormalizeNotDenormalizer(): void
    {
        $this->expectException(RuntimeException::class);
        $this->buildNormalizer()->denormalize(new stdClass(), 'foo');
    }

    public function testDenormalizeNotArray(): void
    {
        $this->expectException(RuntimeException::class);
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $n = $this->buildNormalizer();
        $n->setDenormalizer($denormalizer);
        $n->denormalize(new stdClass(), 'foo');
    }

    public function testDenormalizeNotClass(): void
    {
        $this->expectException(RuntimeException::class);
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $n = $this->buildNormalizer();
        $n->setDenormalizer($denormalizer);
        $n->denormalize(['foo' => 'bar'], 'foo');
    }

    public function testDenormalize(): void
    {
        $env = new Environment();

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects($this->once())
            ->method('denormalize')
            ->with(['name' => 'foo'], Environment::class)
            ->willReturn($env);

        $n = $this->buildNormalizer();
        $n->setDenormalizer($denormalizer);
        $this->assertEquals($env, $n->denormalize(['@class' => Environment::class, 'name' => 'foo'], 'foo'));
    }

    public function testGetSupportedTypes(): void
    {
        $this->assertIsArray($this->buildNormalizer()->getSupportedTypes('array'));
    }
}
