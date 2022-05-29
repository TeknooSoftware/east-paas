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

namespace Teknoo\East\Paas\Recipe\Cookbook;

use Teknoo\East\Paas\Contracts\Recipe\Cookbook\NewJobInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Job\SendJobInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Worker\DispatchJobInterface;
use Teknoo\East\Paas\Recipe\Step\Job\CreateNewJob;
use Teknoo\East\Paas\Recipe\Step\Job\PrepareJob;
use Teknoo\East\Paas\Recipe\Step\Job\SaveJob;
use Teknoo\East\Paas\Recipe\Step\Job\SerializeJob;
use Teknoo\East\Paas\Recipe\Step\Misc\DispatchError;
use Teknoo\East\Paas\Recipe\Step\Misc\GetVariables;
use Teknoo\East\Paas\Recipe\Step\Project\GetEnvironment;
use Teknoo\East\Paas\Recipe\Step\Project\GetProject;
use Teknoo\East\Paas\Recipe\Traits\AdditionalStepsTrait;
use Teknoo\Recipe\Bowl\Bowl;
use Teknoo\Recipe\Cookbook\BaseCookbookTrait;
use Teknoo\Recipe\RecipeInterface;

/**
 * Cookbook to create a new job, aka a new deployment from a project, but not run it.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class NewJob implements NewJobInterface
{
    use BaseCookbookTrait;
    use AdditionalStepsTrait;

    /**
     * @param iterable<int, callable> $additionalSteps
     * @param iterable<callable> $additionalErrorHandlers
     */
    public function __construct(
        RecipeInterface $recipe,
        private GetProject $stepGetProject,
        private GetEnvironment $stepGetEnvironment,
        private GetVariables $stepGetVariables,
        private CreateNewJob $stepCreateNewJob,
        private PrepareJob $stepPrepareJob,
        private SaveJob $stepSaveJob,
        private SerializeJob $stepSerializeJob,
        iterable $additionalSteps,
        private DispatchJobInterface $stepDispatchJob,
        private SendJobInterface $stepSendJob,
        private DispatchError $stepDispatchError,
        iterable $additionalErrorHandlers,
    ) {
        $this->additionalSteps = $additionalSteps;
        $this->additionalErrorHandlers = $additionalErrorHandlers;
        $this->fill($recipe);
    }

    protected function populateRecipe(RecipeInterface $recipe): RecipeInterface
    {
        $recipe = $recipe->cook($this->stepGetProject, GetProject::class, [], 10);
        $recipe = $recipe->cook($this->stepGetEnvironment, GetEnvironment::class, [], 20);
        $recipe = $recipe->cook($this->stepGetVariables, GetVariables::class, [], 30);
        $recipe = $recipe->cook($this->stepCreateNewJob, CreateNewJob::class, [], 40);
        $recipe = $recipe->cook($this->stepPrepareJob, PrepareJob::class, [], 50);
        $recipe = $recipe->cook($this->stepSaveJob, SaveJob::class, [], 60);
        $recipe = $recipe->cook($this->stepSerializeJob, SerializeJob::class, [], 70);

        $recipe = $this->registerAdditionalSteps($recipe, $this->additionalSteps);

        $recipe = $recipe->cook(
            $this->stepDispatchJob,
            DispatchJobInterface::class,
            [],
            90
        );

        $recipe = $recipe->cook($this->stepSendJob, SendJobInterface::class, [], 100);

        $recipe = $this->registerAdditionalErrorHandler($recipe, $this->additionalErrorHandlers);

        $recipe = $recipe->onError(new Bowl($this->stepDispatchError, ['result' => 'exception']));

        return $recipe;
    }
}
