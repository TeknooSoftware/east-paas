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

use Teknoo\East\Paas\Contracts\Recipe\Cookbook\RunJobInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\SendHistoryInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Job\DispatchResultInterface;
use Teknoo\East\Paas\Recipe\Step\Job\DeserializeJob;
use Teknoo\East\Paas\Recipe\Step\Job\ReceiveJob;
use Teknoo\East\Paas\Recipe\Step\Misc\Ping;
use Teknoo\East\Paas\Recipe\Step\Misc\SetTimeLimit;
use Teknoo\East\Paas\Recipe\Step\Misc\UnsetTimeLimit;
use Teknoo\East\Paas\Recipe\Step\Worker\BuildImages;
use Teknoo\East\Paas\Recipe\Step\Worker\BuildVolumes;
use Teknoo\East\Paas\Recipe\Step\Worker\CloneRepository;
use Teknoo\East\Paas\Recipe\Step\Worker\CompileDeployment;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureCloningAgent;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureClusterClient;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureConductor;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureImagesBuilder;
use Teknoo\East\Paas\Recipe\Step\Worker\Deploying;
use Teknoo\East\Paas\Recipe\Step\Worker\Exposing;
use Teknoo\East\Paas\Recipe\Step\Worker\HookingDeployment;
use Teknoo\East\Paas\Recipe\Step\Worker\PrepareWorkspace;
use Teknoo\East\Paas\Recipe\Step\Worker\ReadDeploymentConfiguration;
use Teknoo\East\Paas\Recipe\Traits\AdditionalStepsTrait;
use Teknoo\Recipe\Bowl\Bowl;
use Teknoo\Recipe\Bowl\BowlInterface;
use Teknoo\Recipe\Cookbook\BaseCookbookTrait;
use Teknoo\Recipe\RecipeInterface;

/**
 * Cookbook to run a created job via the cookbook NewJob, aka a project deployment.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class RunJob implements RunJobInterface
{
    use BaseCookbookTrait;
    use AdditionalStepsTrait;

    /**
     * @param iterable<int, callable> $additionalSteps
     */
    public function __construct(
        RecipeInterface $recipe,
        private readonly DispatchHistoryInterface $stepDispatchHistory,
        private readonly Ping $stepPing,
        private readonly SetTimeLimit $stepSetTimeLimit,
        private readonly ReceiveJob $stepReceiveJob,
        private readonly DeserializeJob $stepDeserializeJob,
        private readonly PrepareWorkspace $stepPrepareWorkspace,
        private readonly ConfigureCloningAgent $stepConfigureCloningAgent,
        private readonly CloneRepository $stepCloneRepository,
        private readonly ConfigureConductor $stepConfigureConductor,
        private readonly ReadDeploymentConfiguration $stepReadDeploymentConfiguration,
        private readonly CompileDeployment $stepCompileDeployment,
        private readonly HookingDeployment $stepHookingDeployment,
        private readonly ConfigureImagesBuilder $stepConfigureImagesBuilder,
        private readonly BuildImages $stepBuildImages,
        private readonly BuildVolumes $stepBuildVolumes,
        private readonly ConfigureClusterClient $stepConfigureClusterClient,
        private readonly Deploying $stepDeploying,
        private readonly Exposing $stepExposing,
        iterable $additionalSteps,
        private readonly DispatchResultInterface $stepDispatchResult,
        private readonly UnsetTimeLimit $stepUnsetTimeLimit,
        private readonly SendHistoryInterface $stepSendHistoryInterface,
    ) {
        $this->additionalSteps = $additionalSteps;
        $this->fill($recipe);
    }


    protected function populateRecipe(RecipeInterface $recipe): RecipeInterface
    {
        $notification = $this->stepDispatchHistory;
        $notificationMapping = ['step' => BowlInterface::METHOD_NAME];

        //Startup Run
        $recipe = $recipe->cook(
            $this->stepPing,
            Ping::class,
            [],
            RunJobInterface::STEP_CONFIG_PING,
        );

        $recipe = $recipe->cook(
            $this->stepSetTimeLimit,
            SetTimeLimit::class,
            [],
            RunJobInterface::STEP_SET_TIMEOUT,
        );

        $recipe = $recipe->cook(
            $this->stepReceiveJob,
            ReceiveJob::class,
            [],
            RunJobInterface::STEP_RECEIVE_JOB,
        );

        $recipe = $recipe->cook(
            $this->stepDeserializeJob,
            DeserializeJob::class,
            [],
            RunJobInterface::STEP_DESERIALIZE_JOB,
        );

        //Prepare workspace
        $recipe = $recipe->cook(
            $notification,
            PrepareWorkspace::class,
            $notificationMapping,
            RunJobInterface::STEP_PREPARE_WORKSPACE,
        );
        $recipe = $recipe->cook(
            $this->stepPrepareWorkspace,
            PrepareWorkspace::class,
            [],
            RunJobInterface::STEP_PREPARE_WORKSPACE,
        );

        $recipe = $recipe->cook(
            $notification,
            ConfigureCloningAgent::class,
            $notificationMapping,
            RunJobInterface::STEP_CONFIGURE_CLONING_AGENT,
        );
        $recipe = $recipe->cook(
            $this->stepConfigureCloningAgent,
            ConfigureCloningAgent::class,
            [],
            RunJobInterface::STEP_CONFIGURE_CLONING_AGENT,
        );

        $recipe = $recipe->cook(
            $notification,
            CloneRepository::class,
            $notificationMapping,
            RunJobInterface::STEP_CLONE_REPOSITORY,
        );
        $recipe = $recipe->cook(
            $this->stepCloneRepository,
            CloneRepository::class,
            [],
            RunJobInterface::STEP_CLONE_REPOSITORY,
        );

        //Configure Deployment
        $recipe = $recipe->cook(
            $notification,
            ConfigureConductor::class,
            $notificationMapping,
            RunJobInterface::STEP_CONFIGURE_CONDUCTOR,
        );
        $recipe = $recipe->cook(
            $this->stepConfigureConductor,
            ConfigureConductor::class,
            [],
            RunJobInterface::STEP_CONFIGURE_CONDUCTOR,
        );

        $recipe = $recipe->cook(
            $notification,
            ReadDeploymentConfiguration::class,
            $notificationMapping,
            RunJobInterface::STEP_READ_DEPLOYMENT_CONFIGURATION,
        );
        $recipe = $recipe->cook(
            $this->stepReadDeploymentConfiguration,
            ReadDeploymentConfiguration::class,
            [],
            RunJobInterface::STEP_READ_DEPLOYMENT_CONFIGURATION,
        );

        $recipe = $recipe->cook(
            $notification,
            CompileDeployment::class,
            $notificationMapping,
            RunJobInterface::STEP_COMPILE_DEPLOYMENT,
        );
        $recipe = $recipe->cook(
            $this->stepCompileDeployment,
            CompileDeployment::class,
            [],
            RunJobInterface::STEP_COMPILE_DEPLOYMENT,
        );

        //Configure Build Image
        $recipe = $recipe->cook(
            $notification,
            HookingDeployment::class,
            $notificationMapping,
            RunJobInterface::STEP_HOOK_PRE_BUILD_CONTAINER,
        );
        $recipe = $recipe->cook(
            $this->stepHookingDeployment,
            HookingDeployment::class,
            [],
            RunJobInterface::STEP_HOOK_PRE_BUILD_CONTAINER,
        );

        $recipe = $recipe->cook(
            $notification,
            ConfigureImagesBuilder::class,
            $notificationMapping,
            RunJobInterface::STEP_CONNECT_CONTAINER_REPOSITORY,
        );
        $recipe = $recipe->cook(
            $this->stepConfigureImagesBuilder,
            ConfigureImagesBuilder::class,
            [],
            RunJobInterface::STEP_CONNECT_CONTAINER_REPOSITORY,
        );

        $recipe = $recipe->cook(
            $notification,
            BuildImages::class,
            $notificationMapping,
            RunJobInterface::STEP_BUILD_IMAGE,
        );
        $recipe = $recipe->cook(
            $this->stepBuildImages,
            BuildImages::class,
            [],
            RunJobInterface::STEP_BUILD_IMAGE,
        );

        $recipe = $recipe->cook(
            $notification,
            BuildVolumes::class,
            $notificationMapping,
            RunJobInterface::STEP_BUILD_VOLUME,
        );
        $recipe = $recipe->cook(
            $this->stepBuildVolumes,
            BuildVolumes::class,
            [],
            RunJobInterface::STEP_BUILD_VOLUME,
        );

        //Do Deployment
        $recipe = $recipe->cook(
            $notification,
            ConfigureClusterClient::class,
            $notificationMapping,
            RunJobInterface::STEP_CONNECT_MASTER,
        );
        $recipe = $recipe->cook(
            $this->stepConfigureClusterClient,
            ConfigureClusterClient::class,
            [],
            RunJobInterface::STEP_CONNECT_MASTER,
        );

        $recipe = $recipe->cook(
            $notification,
            Deploying::class,
            $notificationMapping,
            RunJobInterface::STEP_DEPLOYING,
        );
        $recipe = $recipe->cook(
            $this->stepDeploying,
            Deploying::class,
            [],
            RunJobInterface::STEP_DEPLOYING,
        );

        $recipe = $recipe->cook(
            $notification,
            Exposing::class,
            $notificationMapping,
            RunJobInterface::STEP_EXPOSING,
        );
        $recipe = $recipe->cook(
            $this->stepExposing,
            Exposing::class,
            [],
            RunJobInterface::STEP_EXPOSING,
        );

        $recipe = $this->registerAdditionalSteps($recipe, $this->additionalSteps);

        //Final
        $recipe = $recipe->cook(
            $this->stepDispatchResult,
            DispatchResultInterface::class,
            [],
            RunJobInterface::STEP_FINAL
        );

        $recipe = $recipe->cook(
            $this->stepSendHistoryInterface,
            SendHistoryInterface::class,
            [],
            RunJobInterface::STEP_SEND_HISTORY
        );

        $recipe = $recipe->cook(
            $this->stepUnsetTimeLimit,
            UnsetTimeLimit::class,
            [],
            RunJobInterface::STEP_UNSET_TIMEOUT
        );

        $recipe = $recipe->onError(new Bowl($this->stepUnsetTimeLimit, []));
        return $recipe->onError(new Bowl($this->stepDispatchResult, ['result' => 'exception']));
    }
}
