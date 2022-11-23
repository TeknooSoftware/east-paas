<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Job\History;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Job\History\SerialGenerator;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Job\History\SerialGenerator
 */
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