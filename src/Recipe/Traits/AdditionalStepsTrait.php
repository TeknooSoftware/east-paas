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

namespace Teknoo\East\Paas\Recipe\Traits;

use Teknoo\East\Paas\Contracts\Recipe\AdditionalStepsInterface;
use Teknoo\Recipe\Bowl\Bowl;
use Teknoo\Recipe\RecipeInterface;

/**
 * Traits to implements AdditionalStepsInterface in cookbook to allow developper to custom these cookbooks in theirs
 * platforms
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
trait AdditionalStepsTrait
{
    /**
     * @var iterable<int, callable>
     */
    private iterable $additionalSteps;

    /**
     * @var iterable<callable>
     */
    private iterable $additionalErrorHandlers = [];

    /**
     * @param iterable<int, callable> $steps
     */
    private function registerAdditionalSteps(RecipeInterface $recipe, iterable $steps): RecipeInterface
    {
        foreach ($steps as $position => $step) {
            $recipe = $recipe->cook($step, AdditionalStepsInterface::class, [], (int) $position);
        }

        return $recipe;
    }

    /**
     * @param iterable<callable> $handlers
     */
    private function registerAdditionalErrorHandler(RecipeInterface $recipe, iterable $handlers): RecipeInterface
    {
        foreach ($handlers as $handler) {
            $recipe = $recipe->onError(new Bowl($handler, ['result' => 'exception']));
        }

        return $recipe;
    }

    protected function populateRecipe(RecipeInterface $recipe): RecipeInterface
    {
        $recipe = parent::populateRecipe($recipe);

        return $this->registerAdditionalSteps($recipe, $this->additionalSteps);
    }
}
