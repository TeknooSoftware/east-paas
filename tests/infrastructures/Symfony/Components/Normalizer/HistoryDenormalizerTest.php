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
use Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\HistoryDenormalizer;
use Teknoo\East\Paas\Object\History;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\HistoryDenormalizer
 */
class HistoryDenormalizerTest extends TestCase
{
    public function buildNormalizer(): HistoryDenormalizer
    {
        return new HistoryDenormalizer();
    }

    public function testSupportsDenormalization()
    {
        self::assertFalse($this->buildNormalizer()->supportsDenormalization(new \stdClass(), 'foo'));
        self::assertFalse($this->buildNormalizer()->supportsDenormalization(['foo'=>'bar'], 'foo'));
        self::assertTrue($this->buildNormalizer()->supportsDenormalization(['foo'=>'bar'], History::class));
    }

    public function testDenormalizeNotArray()
    {
        $this->expectException(\RuntimeException::class);
        $this->buildNormalizer()->denormalize(new \stdClass(), 'foo');
    }

    public function testDenormalizeNotClassParameter()
    {
        $this->expectException(\RuntimeException::class);
        $this->buildNormalizer()->denormalize(['foo'=>'bar'], 'foo');
    }


    public function testDenormalizeWithWrongDate()
    {
        $this->expectException(\RuntimeException::class);
        $this->buildNormalizer()->denormalize(['message' => 'foo', 'date' => 'fooo'], History::class);
    }

    /**
     * @throws \Exception
     */
    public function testDenormalize()
    {
        self::assertEquals(
            new History(null, 'foo', new \DateTimeImmutable('2018-05-01 00:00:00 +0000')),
            $this->buildNormalizer()->denormalize(['message' => 'foo', 'date' => '2018-05-01 00:00:00 +0000'], History::class)
        );

        self::assertEquals(
            new History(null, 'foo', new \DateTimeImmutable('2018-05-01 00:00:00 +0000'), true),
            $this->buildNormalizer()->denormalize(['message' => 'foo', 'date' => '2018-05-01 00:00:00 +0000', 'is_final' => true], History::class)
        );

        self::assertEquals(
            new History(null, 'foo', new \DateTimeImmutable('2018-05-01 00:00:00 +0000'), false),
            $this->buildNormalizer()->denormalize(['message' => 'foo', 'date' => '2018-05-01 00:00:00 +0000', 'is_final' => false], History::class)
        );

        self::assertEquals(
            new History(null, 'foo', new \DateTimeImmutable('2018-05-01 00:00:00 +0000'), false, ['hello' => 'world']),
            $this->buildNormalizer()->denormalize(['message' => 'foo', 'date' => '2018-05-01 00:00:00 +0000', 'is_final' => false, 'extra' => ['hello' => 'world']], History::class)
        );

        self::assertEquals(
            new History(new History(null, 'bar', new \DateTimeImmutable('2018-04-01 00:00:00 +0000')), 'foo', new \DateTimeImmutable('2018-05-01 00:00:00 +0000'), false, ['hello' => 'world']),
            $this->buildNormalizer()->denormalize(['previous' => ['message' => 'bar', 'date' => '2018-04-01 00:00:00 +0000'], 'message' => 'foo', 'date' => '2018-05-01 00:00:00 +0000', 'is_final' => false, 'extra' => ['hello' => 'world']], History::class)
        );
    }
}
