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

namespace Teknoo\Tests\East\Paas\Recipe\Cookbook;

use Teknoo\East\Foundation\Recipe\RecipeInterface;
use Teknoo\Recipe\ChefInterface;
use Teknoo\Recipe\CookbookInterface;

trait CookbookTestTrait
{
    abstract public function buildCookbook(): CookbookInterface;

    public function testTrainWithBadChef()
    {
        $this->expectException(\TypeError::class);

        $this->buildCookbook()->train(new \stdClass());
    }

    public function testTrain()
    {
        $cookbook = $this->buildCookbook();

        self::assertInstanceOf(
            CookbookInterface::class,
            $cookbook->train($this->createMock(ChefInterface::class))
        );

        self::assertInstanceOf(
            CookbookInterface::class,
            $cookbook->train($this->createMock(ChefInterface::class))
        );
    }

    public function testPrepareWithBadWorkplan()
    {
        $this->expectException(\TypeError::class);

        $this->buildCookbook()->train(new \stdClass(), $this->createMock(ChefInterface::class));
    }

    public function testPrepareWithBadChef()
    {
        $this->expectException(\TypeError::class);

        $this->buildCookbook()->train([], $this->createMock(ChefInterface::class));
    }

    public function testPrepare()
    {
        $workplan = [];
        self::assertInstanceOf(
            CookbookInterface::class,
            $this->buildCookbook()->prepare($workplan, $this->createMock(ChefInterface::class))
        );
    }

    public function testValidate()
    {
        self::assertInstanceOf(
            CookbookInterface::class,
            $this->buildCookbook()->validate('foo')
        );
    }

    public function testFillWithBadRecipe()
    {
        $this->expectException(\TypeError::class);

        $this->buildCookbook()->fill(new \stdClass());
    }

    public function testFillWithRecipe()
    {
        self::assertInstanceOf(
            CookbookInterface::class,
            $this->buildCookbook()->fill($this->createMock(RecipeInterface::class))
        );
    }
}