<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\SerializingNormalier;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\ExceptionNormalizer;
use Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\HistoryDenormalizer;
use Teknoo\East\Paas\Object\History;

/**
 * @covers \Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\ExceptionNormalizer
 */
class ExceptionNormalizerTest extends TestCase
{
    public function buildNormalizer()
    {
        return new ExceptionNormalizer();
    }

    public function testSupportsNormalization()
    {
        self::assertFalse($this->buildNormalizer()->supportsNormalization(new \stdClass()));
        self::assertTrue($this->buildNormalizer()->supportsNormalization(new \Exception()));
    }

    public function testNormalizeNotException()
    {
        $this->expectException(\LogicException::class);
        $this->buildNormalizer()->normalize(new \stdClass());
    }

    public function testNormalize()
    {
        self::assertEquals(
            array(
                'class' => 'Exception',
                'message' => 'foo',
                'code' => 123,
                'file' => __FILE__,
                'line' => 49,
            ),
            $this->buildNormalizer()->normalize(new \Exception('foo', 123))
        );
    }
}
