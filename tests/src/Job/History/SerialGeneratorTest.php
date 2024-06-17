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

namespace Teknoo\Tests\East\Paas\Job\History;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Job\History\SerialGenerator;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(SerialGenerator::class)]
class SerialGeneratorTest extends TestCase
{
    public function testGeneration()
    {
        $generator = new SerialGenerator(123);
        self::assertEquals(124, $generator->getNewSerialNumber());
        self::assertEquals(125, $generator->getNewSerialNumber());
        self::assertEquals(126, $generator->getNewSerialNumber());
        self::assertEquals(127, $generator->getNewSerialNumber());
    }

    public function testGenerationWithAnotherGenerator()
    {
        $generator = new SerialGenerator(1);
        self::assertInstanceOf(
            SerialGenerator::class,
            $generator->setGenerator(fn ($number) => $number*2)
        );

        self::assertEquals(2, $generator->getNewSerialNumber());
        self::assertEquals(4, $generator->getNewSerialNumber());
        self::assertEquals(8, $generator->getNewSerialNumber());
        self::assertEquals(16, $generator->getNewSerialNumber());
    }
}