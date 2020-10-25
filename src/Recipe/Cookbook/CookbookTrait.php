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

namespace Teknoo\East\Paas\Recipe\Cookbook;

use Teknoo\Recipe\BaseRecipeInterface;
use Teknoo\Recipe\ChefInterface;
use Teknoo\Recipe\CookbookInterface;
use Teknoo\Recipe\RecipeInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
trait CookbookTrait
{
    private bool $recipePopulated = false;

    private RecipeInterface $recipe;

    private function getRecipe(): RecipeInterface
    {
        if ($this->recipePopulated) {
            return $this->recipe;
        }

        $this->recipe = $this->populateRecipe($this->recipe);
        $this->recipePopulated = true;

        return $this->recipe;
    }

    public function train(ChefInterface $chef): BaseRecipeInterface
    {
        $chef->read($this->getRecipe());

        return $this;
    }

    public function prepare(array &$workPlan, ChefInterface $chef): BaseRecipeInterface
    {
        $this->getRecipe()->prepare($workPlan, $chef);

        return $this;
    }

    public function validate($value): BaseRecipeInterface
    {
        $this->getRecipe()->validate($value);

        return $this;
    }

    public function fill(RecipeInterface $recipe): CookbookInterface
    {
        $this->recipe = $recipe;
        $this->recipePopulated = false;

        return $this;
    }
}