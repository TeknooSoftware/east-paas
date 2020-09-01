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
use Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\ExceptionNormalizer;
use Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\HistoryDenormalizer;
use Teknoo\East\Paas\Object\History;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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
                'line' => 66,
            ),
            $this->buildNormalizer()->normalize(new \Exception('foo', 123))
        );
    }
}
