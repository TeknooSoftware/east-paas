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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Serializing;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Normalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface as SymfonyNormalizerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use TypeError;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 * @package Teknoo\Tests\East\Paas\Infrastructures\Symfony\SerializingSerializing
 */

#[CoversClass(Normalizer::class)]
class NormalizerTest extends TestCase
{
    private (SymfonyNormalizerInterface&MockObject)|(SymfonyNormalizerInterface&Stub)|null $normalizer = null;

    private function getSfNormalizerMock(bool $stub = false): (SymfonyNormalizerInterface&Stub)|(SymfonyNormalizerInterface&MockObject)
    {
        if (!$this->normalizer instanceof SymfonyNormalizerInterface) {
            if ($stub) {
                $this->normalizer = $this->createStub(SymfonyNormalizerInterface::class);
            } else {
                $this->normalizer = $this->createMock(SymfonyNormalizerInterface::class);
            }
        }

        return $this->normalizer;
    }

    public function buindNormalizer(): Normalizer
    {
        return new Normalizer(
            $this->getSfNormalizerMock(true)
        );
    }

    public function testNormalizeWrongPromise(): void
    {
        $this->expectException(TypeError::class);
        $this->buindNormalizer()->normalize(
            new stdClass(),
            new stdClass(),
            'foo',
            []
        );
    }

    public function testNormalizeWrongFormat(): void
    {
        $this->expectException(TypeError::class);
        $this->buindNormalizer()->normalize(
            new stdClass(),
            $this->createStub(PromiseInterface::class),
            new stdClass(),
            []
        );
    }

    public function testNormalizeWrongContext(): void
    {
        $this->expectException(TypeError::class);
        $this->buindNormalizer()->normalize(
            new stdClass(),
            $this->createStub(PromiseInterface::class),
            'foo',
            new stdClass()
        );
    }

    public function testNormalizeGood(): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $this->getSfNormalizerMock(true)
            ->method('normalize')
            ->willReturn(['foo' => 'bar']);

        $this->assertInstanceOf(Normalizer::class, $this->buindNormalizer()->normalize(
            new stdClass(),
            $promise,
            'foo',
            []
        ));
    }

    public function testNormalizeFail(): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->getSfNormalizerMock(true)
            ->method('normalize')
            ->willThrowException(new Exception('foo'));

        $this->assertInstanceOf(Normalizer::class, $this->buindNormalizer()->normalize(
            new stdClass(),
            $promise,
            'foo',
            []
        ));

    }
}
