<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Recipe\Plan;

use Teknoo\East\Paas\Contracts\Recipe\Plan\NewJobInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Job\SendJobInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Worker\DispatchJobInterface;
use Teknoo\East\Paas\Recipe\Step\Job\CreateNewJob;
use Teknoo\East\Paas\Recipe\Step\Job\PrepareJob;
use Teknoo\East\Paas\Recipe\Step\Job\SaveJob;
use Teknoo\East\Paas\Recipe\Step\Job\SerializeJob;
use Teknoo\East\Paas\Recipe\Step\Misc\DispatchError;
use Teknoo\East\Paas\Recipe\Step\Misc\GetVariables;
use Teknoo\East\Paas\Recipe\Step\Misc\Ping;
use Teknoo\East\Paas\Recipe\Step\Misc\SetTimeLimit;
use Teknoo\East\Paas\Recipe\Step\Misc\UnsetTimeLimit;
use Teknoo\East\Paas\Recipe\Step\Project\GetEnvironment;
use Teknoo\East\Paas\Recipe\Step\Project\GetProject;
use Teknoo\Recipe\Bowl\Bowl;
use Teknoo\Recipe\Plan\EditablePlanTrait;
use Teknoo\Recipe\Ingredient\Ingredient;
use Teknoo\Recipe\RecipeInterface;

/**
 * Plan to create a new job, aka a new deployment from a project, but not run it.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class NewJob implements NewJobInterface
{
    use EditablePlanTrait;

    public function __construct(
        RecipeInterface $recipe,
        private readonly Ping $stepPing,
        private readonly SetTimeLimit $stepSetTimeLimit,
        private readonly GetProject $stepGetProject,
        private readonly GetEnvironment $stepGetEnvironment,
        private readonly GetVariables $stepGetVariables,
        private readonly CreateNewJob $stepCreateNewJob,
        private readonly PrepareJob $stepPrepareJob,
        private readonly SaveJob $stepSaveJob,
        private readonly SerializeJob $stepSerializeJob,
        private readonly DispatchJobInterface $stepDispatchJob,
        private readonly SendJobInterface $stepSendJob,
        private readonly UnsetTimeLimit $stepUnsetTimeLimit,
        private readonly DispatchError $stepDispatchError,
    ) {
        $this->fill($recipe);
    }

    protected function populateRecipe(RecipeInterface $recipe): RecipeInterface
    {
        $recipe = $recipe->require(new Ingredient('string', 'projectId'));
        $recipe = $recipe->require(new Ingredient('string', 'envName'));

        $recipe = $recipe->cook($this->stepPing, Ping::class, [], 5);
        $recipe = $recipe->cook($this->stepSetTimeLimit, SetTimeLimit::class, [], 6);
        $recipe = $recipe->cook($this->stepGetProject, GetProject::class, [], 10);
        $recipe = $recipe->cook($this->stepGetEnvironment, GetEnvironment::class, [], 20);
        $recipe = $recipe->cook($this->stepGetVariables, GetVariables::class, [], 30);
        $recipe = $recipe->cook($this->stepCreateNewJob, CreateNewJob::class, [], 40);
        $recipe = $recipe->cook($this->stepPrepareJob, PrepareJob::class, [], 50);
        $recipe = $recipe->cook($this->stepSaveJob, SaveJob::class, [], 60);
        $recipe = $recipe->cook($this->stepSerializeJob, SerializeJob::class, [], 70);

        $recipe = $recipe->cook(
            $this->stepDispatchJob,
            DispatchJobInterface::class,
            [],
            90
        );

        $recipe = $recipe->cook($this->stepSendJob, SendJobInterface::class, [], 100);
        $recipe = $recipe->cook($this->stepUnsetTimeLimit, UnsetTimeLimit::class, [], 110);

        $recipe = $recipe->onError(new Bowl($this->stepUnsetTimeLimit, []));
        return $recipe->onError(new Bowl($this->stepDispatchError, ['result' => 'exception']));
    }
}
