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

use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use stdClass;
use Teknoo\East\FoundationBundle\Normalizer\EastNormalizer as EastNormalizerBase;
use Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\EastNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\East\Paas\Object\Job;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(EastNormalizer::class)]
class EastNormalizerTest extends TestCase
{
    public function buildNormalizer(): EastNormalizer
    {
        return new EastNormalizer();
    }

    public function testNormalizeBadObject(): void
    {
        $this->expectException(RuntimeException::class);
        $this->buildNormalizer()->normalize(new stdClass());
    }

    public function testNormalize(): void
    {
        $object = $this->createMock(NormalizableInterface::class);
        $normalizer = $this->buildNormalizer();

        $returnValue = ['foo' => 'bar'];
        $context = ['context' => 'hello'];

        $object->expects($this->once())
            ->method('exportToMeData')
            ->willReturnCallback(function ($nrmlz, $ctxt) use ($normalizer, $object, $context, $returnValue): MockObject|Stub {
                $this->assertInstanceOf(EastNormalizerBase::class, $nrmlz);
                $this->assertNotSame($normalizer, $nrmlz);
                $this->assertEquals($context, $ctxt);

                $nrmlz->injectData($returnValue);

                return $object;
            });

        $this->assertEquals($returnValue, $normalizer->normalize($object, 'json', $context));
    }

    public function testNormalizeWithJob(): void
    {
        $object = $this->createMock(Job::class);
        $normalizer = $this->buildNormalizer();

        $returnValue = [
            'foo' => 'bar',
            'bar' => 'foo'
        ];
        $context = ['context' => 'hello', 'add' => ['bar' => 'foo']];

        $object->expects($this->once())
            ->method('exportToMeData')
            ->willReturnCallback(function ($nrmlz, $ctxt) use ($normalizer, $object, $context, $returnValue): MockObject|Stub {
                $this->assertInstanceOf(EastNormalizerBase::class, $nrmlz);
                $this->assertNotSame($normalizer, $nrmlz);
                $this->assertEquals($context, $ctxt);

                $nrmlz->injectData($returnValue);

                return $object;
            });

        $this->assertEquals($returnValue, $normalizer->normalize($object, 'json', $context));
    }

    public function testNormalizeWithAwareNormalizerWithOnlyScalarValues(): void
    {
        $object = $this->createMock(NormalizableInterface::class);
        $normalizer = $this->buildNormalizer();

        $returnValue = ['foo' => 'bar'];
        $context = ['context' => 'hello'];

        $object->expects($this->once())
            ->method('exportToMeData')
            ->willReturnCallback(function ($nrmlz, $ctxt) use ($normalizer, $object, $context, $returnValue): MockObject|Stub {
                $this->assertInstanceOf(EastNormalizer::class, $nrmlz);
                $this->assertNotSame($normalizer, $nrmlz);
                $this->assertEquals($context, $ctxt);

                $nrmlz->injectData($returnValue);

                return $object;
            });

        $normalizer2 = $this->createMock(NormalizerInterface::class);
        $normalizer2->expects($this->never())
            ->method('normalize');

        $normalizer->setNormalizer($normalizer2);

        $this->assertEquals($returnValue, $normalizer->normalize($object, 'json', $context));
    }

    public function testNormalizeWithAwareNormalizer(): void
    {
        $object = $this->createMock(NormalizableInterface::class);
        $normalizer = $this->buildNormalizer();

        $returnValue = ['foo' => 'bar', 'bar' => ($date = new DateTime('2018-05-01 02:03:04'))];
        $returnValue2 = ['foo' => 'bar', 'bar' => '2018-05-01 02:03:04'];
        $context = ['context' => 'hello'];

        $object->expects($this->once())
            ->method('exportToMeData')
            ->willReturnCallback(function ($nrmlz, $ctxt) use ($normalizer, $object, $context, $returnValue): MockObject|Stub {
                $this->assertInstanceOf(EastNormalizer::class, $nrmlz);
                $this->assertNotSame($normalizer, $nrmlz);
                $this->assertEquals($context, $ctxt);

                $nrmlz->injectData($returnValue);

                return $object;
            });

        $normalizer2 = $this->createMock(NormalizerInterface::class);
        $normalizer2->expects($this->once())
            ->method('normalize')
            ->with($date, 'json', $context)
            ->willReturn('2018-05-01 02:03:04');

        $normalizer->setNormalizer($normalizer2);

        $this->assertEquals($returnValue2, $normalizer->normalize($object, 'json', $context));
    }
}
