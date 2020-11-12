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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Cookbook;

use Psr\Http\Message\ServerRequestInterface;
use Teknoo\East\Paas\Contracts\Recipe\Cookbook\AddHistoryInterface;
use Teknoo\East\Paas\Recipe\Step\History\AddHistory as StepAddHistory;
use Teknoo\East\Paas\Recipe\Step\History\DeserializeHistory;
use Teknoo\East\Paas\Recipe\Step\History\DisplayHistory;
use Teknoo\East\Paas\Recipe\Step\History\ReceiveHistory;
use Teknoo\East\Paas\Recipe\Step\History\SerializeHistory;
use Teknoo\East\Paas\Recipe\Step\Job\GetJob;
use Teknoo\East\Paas\Recipe\Step\Job\SaveJob;
use Teknoo\East\Paas\Recipe\Step\Misc\DisplayError;
use Teknoo\East\Paas\Recipe\Step\Project\GetProject;
use Teknoo\Recipe\Bowl\Bowl;
use Teknoo\Recipe\Ingredient\Ingredient;
use Teknoo\Recipe\RecipeInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class AddHistory implements AddHistoryInterface
{
    use CookbookTrait;

    private ReceiveHistory $stepReceiveHistory;

    private DeserializeHistory $stepDeserializeHistory;

    private GetProject $stepGetProject;

    private GetJob $stepGetJob;

    private StepAddHistory $stepAddHistory;

    private SaveJob $stepSaveJob;

    private SerializeHistory $stepSerializeHistory;

    private DisplayHistory $stepDisplayHistory;

    private DisplayError $stepDisplayError;

    public function __construct(
        RecipeInterface $recipe,
        ReceiveHistory $stepReceiveHistory,
        DeserializeHistory $stepDeserializeHistory,
        GetProject $stepGetProject,
        GetJob $stepGetJob,
        StepAddHistory $stepAddHistory,
        SaveJob $stepSaveJob,
        SerializeHistory $stepSerializeHistory,
        DisplayHistory $stepDisplayHistory,
        DisplayError $stepDisplayError
    ) {
        $this->stepReceiveHistory = $stepReceiveHistory;
        $this->stepDeserializeHistory = $stepDeserializeHistory;
        $this->stepGetProject = $stepGetProject;
        $this->stepGetJob = $stepGetJob;
        $this->stepAddHistory = $stepAddHistory;
        $this->stepSaveJob = $stepSaveJob;
        $this->stepSerializeHistory = $stepSerializeHistory;
        $this->stepDisplayHistory = $stepDisplayHistory;
        $this->stepDisplayError = $stepDisplayError;

        $this->fill($recipe);
    }

    protected function populateRecipe(RecipeInterface $recipe): RecipeInterface
    {
        $recipe = $recipe->require(new Ingredient(ServerRequestInterface::class, 'request'));

        $recipe = $recipe->cook($this->stepReceiveHistory, ReceiveHistory::class, [], 0);
        $recipe = $recipe->cook($this->stepDeserializeHistory, DeserializeHistory::class, [], 1);
        $recipe = $recipe->cook($this->stepGetProject, GetProject::class, [], 2);
        $recipe = $recipe->cook($this->stepGetJob, GetJob::class, [], 3);
        $recipe = $recipe->cook($this->stepAddHistory, StepAddHistory::class, [], 4);
        $recipe = $recipe->cook($this->stepSaveJob, SaveJob::class, [], 5);
        $recipe = $recipe->cook($this->stepSerializeHistory, SerializeHistory::class, [], 6);
        $recipe = $recipe->cook($this->stepDisplayHistory, DisplayHistory::class, [], 7);

        $recipe = $recipe->onError(new Bowl($this->stepDisplayError, []));

        return $recipe;
    }
}
