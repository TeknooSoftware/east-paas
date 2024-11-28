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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Recipe\Plan;

use Psr\Http\Message\MessageInterface;
use Teknoo\East\Paas\Contracts\Recipe\Plan\AddHistoryInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\SendHistoryInterface;
use Teknoo\East\Paas\Recipe\Step\History\AddHistory as StepAddHistory;
use Teknoo\East\Paas\Recipe\Step\History\DeserializeHistory;
use Teknoo\East\Paas\Recipe\Step\History\ReceiveHistory;
use Teknoo\East\Paas\Recipe\Step\Job\GetJob;
use Teknoo\East\Paas\Recipe\Step\Job\SaveJob;
use Teknoo\East\Paas\Recipe\Step\Misc\DispatchError;
use Teknoo\East\Paas\Recipe\Step\Misc\Ping;
use Teknoo\East\Paas\Recipe\Step\Misc\SetTimeLimit;
use Teknoo\East\Paas\Recipe\Step\Misc\UnsetTimeLimit;
use Teknoo\East\Paas\Recipe\Step\Project\GetProject;
use Teknoo\Recipe\Bowl\Bowl;
use Teknoo\Recipe\Plan\EditablePlanTrait;
use Teknoo\Recipe\Ingredient\Ingredient;
use Teknoo\Recipe\RecipeInterface;

/**
 * Plan to persist a new history in a job to the database.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class AddHistory implements AddHistoryInterface
{
    use EditablePlanTrait;

    public function __construct(
        RecipeInterface $recipe,
        private readonly Ping $stepPing,
        private readonly SetTimeLimit $stepSetTimeLimit,
        private readonly ReceiveHistory $stepReceiveHistory,
        private readonly DeserializeHistory $stepDeserializeHistory,
        private readonly GetProject $stepGetProject,
        private readonly GetJob $stepGetJob,
        private readonly StepAddHistory $stepAddHistory,
        private readonly SaveJob $stepSaveJob,
        private readonly SendHistoryInterface $stepSendHistoryInterface,
        private readonly UnsetTimeLimit $stepUnsetTimeLimit,
        private readonly DispatchError $stepDispatchError,
    ) {
        $this->fill($recipe);
    }

    protected function populateRecipe(RecipeInterface $recipe): RecipeInterface
    {
        $recipe = $recipe->require(new Ingredient(MessageInterface::class));

        $recipe = $recipe->cook($this->stepPing, Ping::class, [], 5);
        $recipe = $recipe->cook($this->stepSetTimeLimit, SetTimeLimit::class, [], 6);
        $recipe = $recipe->cook($this->stepReceiveHistory, ReceiveHistory::class, [], 10);
        $recipe = $recipe->cook($this->stepDeserializeHistory, DeserializeHistory::class, [], 20);
        $recipe = $recipe->cook($this->stepGetProject, GetProject::class, [], 30);
        $recipe = $recipe->cook($this->stepGetJob, GetJob::class, [], 40);
        $recipe = $recipe->cook($this->stepAddHistory, StepAddHistory::class, [], 50);
        $recipe = $recipe->cook($this->stepSaveJob, SaveJob::class, [], 60);

        $recipe = $recipe->cook($this->stepSendHistoryInterface, SendHistoryInterface::class, [], 80);
        $recipe = $recipe->cook($this->stepUnsetTimeLimit, UnsetTimeLimit::class, [], 90);

        $recipe = $recipe->onError(new Bowl($this->stepUnsetTimeLimit, []));
        return $recipe->onError(new Bowl($this->stepDispatchError, ['result' => 'exception']));
    }
}
