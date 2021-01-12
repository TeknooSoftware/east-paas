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
use Teknoo\East\Paas\Contracts\Recipe\Cookbook\NewJobInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Worker\DispatchJobInterface;
use Teknoo\East\Paas\Recipe\Step\Job\CreateNewJob;
use Teknoo\East\Paas\Recipe\Step\Job\DisplayJob;
use Teknoo\East\Paas\Recipe\Step\Job\PrepareJob;
use Teknoo\East\Paas\Recipe\Step\Job\SaveJob;
use Teknoo\East\Paas\Recipe\Step\Job\SerializeJob;
use Teknoo\East\Paas\Recipe\Step\Misc\DisplayError;
use Teknoo\East\Paas\Recipe\Step\Misc\GetVariables;
use Teknoo\East\Paas\Recipe\Step\Project\GetEnvironment;
use Teknoo\East\Paas\Recipe\Step\Project\GetProject;
use Teknoo\Recipe\Bowl\Bowl;
use Teknoo\Recipe\Cookbook\BaseCookbookTrait;
use Teknoo\Recipe\Ingredient\Ingredient;
use Teknoo\Recipe\RecipeInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class NewJob implements NewJobInterface
{
    use BaseCookbookTrait;

    private GetProject $stepGetProject;

    private GetEnvironment $stepGetEnvironment;

    private GetVariables $stepGetVariables;

    private CreateNewJob $stepCreateNewJob;

    private PrepareJob $stepPrepareJob;

    private SaveJob $stepSaveJob;

    private SerializeJob $stepSerializeJob;

    private DispatchJobInterface $stepDispatchJobInterface;

    private DisplayJob $stepDisplayJob;

    private DisplayError $stepDisplayError;

    public function __construct(
        RecipeInterface $recipe,
        GetProject $stepGetProject,
        GetEnvironment $stepGetEnvironment,
        GetVariables $stepGetVariables,
        CreateNewJob $stepCreateNewJob,
        PrepareJob $stepPrepareJob,
        SaveJob $stepSaveJob,
        SerializeJob $stepSerializeJob,
        DispatchJobInterface $stepDispatchJobInterface,
        DisplayJob $stepDisplayJob,
        DisplayError $stepDisplayError
    ) {
        $this->stepGetProject = $stepGetProject;
        $this->stepGetEnvironment = $stepGetEnvironment;
        $this->stepGetVariables = $stepGetVariables;
        $this->stepCreateNewJob = $stepCreateNewJob;
        $this->stepPrepareJob = $stepPrepareJob;
        $this->stepSaveJob = $stepSaveJob;
        $this->stepSerializeJob = $stepSerializeJob;
        $this->stepDispatchJobInterface = $stepDispatchJobInterface;
        $this->stepDisplayJob = $stepDisplayJob;
        $this->stepDisplayError = $stepDisplayError;

        $this->fill($recipe);
    }

    protected function populateRecipe(RecipeInterface $recipe): RecipeInterface
    {
        $recipe = $recipe->require(new Ingredient(ServerRequestInterface::class, 'request'));

        $recipe = $recipe->cook($this->stepGetProject, GetProject::class, [], 0);
        $recipe = $recipe->cook($this->stepGetEnvironment, GetEnvironment::class, [], 1);
        $recipe = $recipe->cook($this->stepGetVariables, GetVariables::class, [], 1);
        $recipe = $recipe->cook($this->stepCreateNewJob, CreateNewJob::class, [], 2);
        $recipe = $recipe->cook($this->stepPrepareJob, PrepareJob::class, [], 3);
        $recipe = $recipe->cook($this->stepSaveJob, SaveJob::class, [], 4);
        $recipe = $recipe->cook($this->stepSerializeJob, SerializeJob::class, [], 5);
        $recipe = $recipe->cook(
            $this->stepDispatchJobInterface,
            DispatchJobInterface::class,
            [],
            6
        );
        $recipe = $recipe->cook($this->stepDisplayJob, DisplayJob::class, [], 7);

        $recipe = $recipe->onError(new Bowl($this->stepDisplayError, []));

        return $recipe;
    }
}
