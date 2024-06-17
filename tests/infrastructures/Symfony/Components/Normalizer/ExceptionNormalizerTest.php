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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\SerializingNormalier;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\ExceptionNormalizer;
use Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\HistoryDenormalizer;
use Teknoo\East\Paas\Object\History;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ExceptionNormalizer::class)]
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
                'line' => 68,
            ),
            $this->buildNormalizer()->normalize(new \Exception('foo', 123))
        );
    }

    public function testGetSupportedTypes()
    {
        self::assertIsArray($this->buildNormalizer()->getSupportedTypes('array'));
    }
}
