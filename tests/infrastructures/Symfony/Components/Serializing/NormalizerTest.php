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
use Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Normalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface as SymfonyNormalizerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @package Teknoo\Tests\East\Paas\Infrastructures\Symfony\SerializingSerializing
 */

#[CoversClass(Normalizer::class)]
class NormalizerTest extends TestCase
{
    private ?SymfonyNormalizerInterface $normalizer = null;

    /**
     * @return SymfonyNormalizerInterface|MockObject
     */
    private function getSfNormalizerMock(): SymfonyNormalizerInterface
    {
        if (!$this->normalizer instanceof SymfonyNormalizerInterface) {
            $this->normalizer = $this->createMock(SymfonyNormalizerInterface::class);
        }

        return $this->normalizer;
    }

    public function buindNormalizer(): Normalizer
    {
        return new Normalizer(
            $this->getSfNormalizerMock()
        );
    }

    public function testNormalizeWrongPromise()
    {
        $this->expectException(\TypeError::class);
        $this->buindNormalizer()->normalize(
            new \stdClass(),
            new \stdClass(),
            'foo',
            []
        );
    }

    public function testNormalizeWrongFormat()
    {
        $this->expectException(\TypeError::class);
        $this->buindNormalizer()->normalize(
            new \stdClass(),
            $this->createMock(PromiseInterface::class),
            new \stdClass(),
            []
        );
    }

    public function testNormalizeWrongContext()
    {
        $this->expectException(\TypeError::class);
        $this->buindNormalizer()->normalize(
            new \stdClass(),
            $this->createMock(PromiseInterface::class),
            'foo',
            new \stdClass()
        );
    }

    public function testNormalizeGood()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $this->getSfNormalizerMock()
            ->expects($this->any())
            ->method('normalize')
            ->willReturn(['foo' => 'bar']);

        self::assertInstanceOf(
            Normalizer::class,
            $this->buindNormalizer()->normalize(
                new \stdClass(),
                $promise,
                'foo',
                []
            )
        );
    }

    public function testNormalizeFail()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->getSfNormalizerMock()
            ->expects($this->any())
            ->method('normalize')
            ->willThrowException(new \Exception('foo'));

        self::assertInstanceOf(
            Normalizer::class,
            $this->buindNormalizer()->normalize(
                new \stdClass(),
                $promise,
                'foo',
                []
            )
        );

    }
}
