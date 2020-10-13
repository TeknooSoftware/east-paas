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

use Psr\Http\Message\ServerRequestInterface;
use Teknoo\East\Paas\Contracts\Recipe\Cookbook\AddHistoryInterface;
use Teknoo\East\Paas\Recipe\Step\History\DeserializeHistory;
use Teknoo\East\Paas\Recipe\Step\History\DisplayHistory;
use Teknoo\East\Paas\Recipe\Step\History\ReceiveHistory;
use Teknoo\East\Paas\Recipe\Step\History\SerializeHistory;
use Teknoo\East\Paas\Recipe\Step\Job\GetJob;
use Teknoo\East\Paas\Recipe\Step\Job\SaveJob;
use Teknoo\East\Paas\Recipe\Step\Misc\DisplayError;
use Teknoo\East\Paas\Recipe\Step\Project\GetProject;
use Teknoo\Recipe\BaseRecipeInterface;
use Teknoo\Recipe\Bowl\Bowl;
use Teknoo\Recipe\ChefInterface;
use Teknoo\Recipe\CookbookInterface;
use Teknoo\Recipe\Ingredient\Ingredient;
use Teknoo\Recipe\RecipeInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class AddHistory implements AddHistoryInterface
{
    private RecipeInterface $recipe;

    private bool $recipePopulated = false;

    protected function populateRecipe(RecipeInterface $recipe): RecipeInterface
    {
        $recipe = $recipe->require(new Ingredient(ServerRequestInterface::class, 'request'));

        $recipe = $recipe->cook($container->get(ReceiveHistory::class), ReceiveHistory::class, [], 0);
        $recipe = $recipe->cook($container->get(DeserializeHistory::class), DeserializeHistory::class, [], 1);
        $recipe = $recipe->cook($container->get(GetProject::class), GetProject::class, [], 2);
        $recipe = $recipe->cook($container->get(GetJob::class), GetJob::class, [], 3);
        $recipe = $recipe->cook($container->get(\Teknoo\East\Paas\Recipe\Step\History\AddHistory::class), AddHistory::class, [], 4);
        $recipe = $recipe->cook($container->get(SaveJob::class), SaveJob::class, [], 5);
        $recipe = $recipe->cook($container->get(SerializeHistory::class), SerializeHistory::class, [], 6);
        $recipe = $recipe->cook($container->get(DisplayHistory::class), DisplayHistory::class, [], 7);

        $recipe = $recipe->onError(new Bowl($container->get(DisplayError::class), []));

        return $recipe;
    }

    private function getRecipe(): RecipeInterface
    {
        if ($this->recipePopulated) {
            return $this->recipe;
        }

        $this->recipe = $this->populateRecipe($this->recipe);
        $this->recipePopulated = true;

        return $this->recipe;
    }

    /**
     * @inheritDoc
     */
    public function train(ChefInterface $chef): BaseRecipeInterface
    {
        $chef->read($this->getRecipe());

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function prepare(array &$workPlan, ChefInterface $chef): BaseRecipeInterface
    {
        $this->getRecipe()->prepare($workPlan, $chef);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function validate($value): BaseRecipeInterface
    {
        $this->getRecipe()->validate($value);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function fill(RecipeInterface $recipe): CookbookInterface
    {
        $this->recipe = $recipe;
        $this->recipePopulated = false;

        return $this;
    }
}
