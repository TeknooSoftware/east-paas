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

use Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Normalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface as SymfonyNormalizerInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @package Teknoo\Tests\East\Paas\Infrastructures\Symfony\SerializingSerializing
 */
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
