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

namespace Teknoo\East\Paas\Recipe\Traits;

use Closure;
use Teknoo\East\Paas\Contracts\Recipe\AdditionalStepsInterface;
use Teknoo\East\Paas\Recipe\Step;
use Teknoo\Recipe\Bowl\Bowl;
use Teknoo\Recipe\RecipeInterface;

use function is_array;
use function is_object;

/**
 * Traits to implements AdditionalStepsInterface in cookbook to allow developper to custom these cookbooks in theirs
 * platforms
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
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
            $with = [];
            if ($step instanceof Step) {
                $with = $step->getWith();
                $step = $step->getStep();
            }

            $class = AdditionalStepsInterface::class;
            if (is_object($step) && !$step instanceof Closure) {
                $class = $step::class;
            } elseif (is_array($step) && is_object($step[0])) {
                $class = $step[0]::class;
            }

            $recipe = $recipe->cook($step, $class, $with, (int) $position);
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
