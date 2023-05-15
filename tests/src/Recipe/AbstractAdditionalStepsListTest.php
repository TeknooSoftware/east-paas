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

namespace Teknoo\Tests\East\Paas\Recipe;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Recipe\AbstractAdditionalStepsList;
use Teknoo\Recipe\Bowl\BowlInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Recipe\AbstractAdditionalStepsList
 */
class AbstractAdditionalStepsListTest extends TestCase
{
    public function testAddBadPriority()
    {
        $this->expectException(\TypeError::class);

        $object = new class extends AbstractAdditionalStepsList {};
        $object->add(new \stdClass(), function() {});
    }

    public function testAddBadCallable()
    {
        $this->expectException(\TypeError::class);

        $object = new class extends AbstractAdditionalStepsList {};
        $object->add(1, new \stdClass());
    }

    public function testAddWithFunction()
    {
        $object = new class extends AbstractAdditionalStepsList {};
        self::assertInstanceOf(
            AbstractAdditionalStepsList::class,
            $object->add(1, function() {})
        );
    }

    public function testAddWithBowl()
    {
        $object = new class extends AbstractAdditionalStepsList {};
        self::assertInstanceOf(
            AbstractAdditionalStepsList::class,
            $object->add(1, $this->createMock(BowlInterface::class))
        );
    }

    public function testGetIterator()
    {
        $object = new class extends AbstractAdditionalStepsList {};
        self::assertInstanceOf(
            AbstractAdditionalStepsList::class,
            $object->add(1, function() {})
        );

        self::assertInstanceOf(
            AbstractAdditionalStepsList::class,
            $object->add(2, function() {})
        );

        $count = 1;
        foreach ($object as $p => $step) {
            self::assertEquals($count++, $p);
            self::assertInstanceOf(\Closure::class, $step);
        }

        self::assertEquals(3, $count);
    }
}
