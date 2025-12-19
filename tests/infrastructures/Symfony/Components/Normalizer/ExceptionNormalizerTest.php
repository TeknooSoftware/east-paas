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

use Exception;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\ExceptionNormalizer;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ExceptionNormalizer::class)]
class ExceptionNormalizerTest extends TestCase
{
    public function buildNormalizer(): ExceptionNormalizer
    {
        return new ExceptionNormalizer();
    }

    public function testSupportsNormalization(): void
    {
        $this->assertFalse($this->buildNormalizer()->supportsNormalization(new stdClass()));
        $this->assertTrue($this->buildNormalizer()->supportsNormalization(new Exception()));
    }

    public function testNormalizeNotException(): void
    {
        $this->expectException(LogicException::class);
        $this->buildNormalizer()->normalize(new stdClass());
    }

    public function testNormalize(): void
    {
        $this->assertEquals([
            'class' => 'Exception',
            'message' => 'foo',
            'code' => 123,
            'file' => __FILE__,
            'line' => 67,
        ], $this->buildNormalizer()->normalize(new Exception('foo', 123)));
    }

    public function testGetSupportedTypes(): void
    {
        $this->assertIsArray($this->buildNormalizer()->getSupportedTypes('array'));
    }
}
