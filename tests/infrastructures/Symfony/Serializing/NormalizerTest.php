<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\SerializingSerializing;

use Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Normalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface as SymfonyNormalizerInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;

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

    /**
     * @covers \Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Normalizer
     */
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
        $promise->expects(self::once())->method('success');
        $promise->expects(self::never())->method('fail');

        $this->getSfNormalizerMock()
            ->expects(self::any())
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
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        $this->getSfNormalizerMock()
            ->expects(self::any())
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
