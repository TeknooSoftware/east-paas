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

use DateTimeImmutable;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\Exception\NotSupportedException;
use Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\HistoryDenormalizer;
use Teknoo\East\Paas\Object\History;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(HistoryDenormalizer::class)]
class HistoryDenormalizerTest extends TestCase
{
    public function buildNormalizer(): HistoryDenormalizer
    {
        return new HistoryDenormalizer();
    }

    public function testSupportsDenormalization(): void
    {
        $this->assertFalse($this->buildNormalizer()->supportsDenormalization(new stdClass(), 'foo'));
        $this->assertFalse($this->buildNormalizer()->supportsDenormalization(['foo' => 'bar'], 'foo'));
        $this->assertTrue($this->buildNormalizer()->supportsDenormalization(['foo' => 'bar'], History::class));
    }

    public function testDenormalizeNotArray(): void
    {
        $this->expectException(RuntimeException::class);
        $this->buildNormalizer()->denormalize(new stdClass(), 'foo');
    }

    public function testDenormalizeNotClassParameter(): void
    {
        $this->expectException(RuntimeException::class);
        $this->buildNormalizer()->denormalize(['foo' => 'bar'], 'foo');
    }

    public function testDenormalizeWithWrongDate(): void
    {
        $this->expectException(RuntimeException::class);
        $this->buildNormalizer()->denormalize(['message' => 'foo', 'date' => 'fooo'], History::class);
    }

    public function testDenormalizeWithWrongMessage(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->buildNormalizer()->denormalize(['message' => 123], History::class);
    }

    public function testDenormalizeWithWrongExtra(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->buildNormalizer()->denormalize(['extra' => 123], History::class);
    }

    public function testDenormalizeWithWrongSerialNumber(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->buildNormalizer()->denormalize(['serial_number' => 'foo'], History::class);
    }

    /**
     * @throws Exception
     */
    public function testDenormalize(): void
    {
        $this->assertEquals(new History(null, 'foo', new DateTimeImmutable('2018-05-01 00:00:00 +0000'), serialNumber: 0, ), $this->buildNormalizer()->denormalize(['message' => 'foo', 'date' => '2018-05-01 00:00:00 +0000', 'serial_number' => 0], History::class));

        $this->assertEquals(new History(null, 'foo', new DateTimeImmutable('2018-05-01 00:00:00 +0000'), true, serialNumber: 0, ), $this->buildNormalizer()->denormalize(['message' => 'foo', 'date' => '2018-05-01 00:00:00 +0000', 'is_final' => true, 'serial_number' => 0], History::class));

        $this->assertEquals(new History(null, 'foo', new DateTimeImmutable('2018-05-01 00:00:00 +0000'), false, serialNumber: 0, ), $this->buildNormalizer()->denormalize(['message' => 'foo', 'date' => '2018-05-01 00:00:00 +0000', 'is_final' => false, 'serial_number' => 0], History::class));

        $this->assertEquals(new History(null, 'foo', new DateTimeImmutable('2018-05-01 00:00:00 +0000'), false, ['hello' => 'world'], serialNumber: 0, ), $this->buildNormalizer()->denormalize(['message' => 'foo', 'date' => '2018-05-01 00:00:00 +0000', 'is_final' => false, 'extra' => ['hello' => 'world'], 'serial_number' => 0], History::class));

        $this->assertEquals(new History(new History(null, 'bar', new DateTimeImmutable('2018-04-01 00:00:00 +0000'), serialNumber: 123, ), 'foo', new DateTimeImmutable('2018-05-01 00:00:00 +0000'), false, ['hello' => 'world'], serialNumber: 0, ), $this->buildNormalizer()->denormalize(['previous' => ['message' => 'bar', 'date' => '2018-04-01 00:00:00 +0000', 'serial_number' => 123], 'message' => 'foo', 'date' => '2018-05-01 00:00:00 +0000', 'is_final' => false, 'extra' => ['hello' => 'world'], 'serial_number' => 0], History::class));
    }

    public function testGetSupportedTypes(): void
    {
        $this->assertIsArray($this->buildNormalizer()->getSupportedTypes('array'));
    }
}
