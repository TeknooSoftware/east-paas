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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Cookbook;

use Teknoo\East\Paas\Contracts\Recipe\AdditionalStepsInterface;
use Teknoo\East\Paas\Contracts\Recipe\Cookbook\RunJobInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Misc\DispatchResultInterface;
use Teknoo\East\Paas\Recipe\Step\History\DisplayHistory;
use Teknoo\East\Paas\Recipe\Step\Job\DeserializeJob;
use Teknoo\East\Paas\Recipe\Step\Job\ReceiveJob;
use Teknoo\East\Paas\Recipe\Step\Misc\DisplayError;
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
use Teknoo\East\Paas\Recipe\Step\Worker\HookBuildContainer;
use Teknoo\East\Paas\Recipe\Step\Worker\PrepareWorkspace;
use Teknoo\East\Paas\Recipe\Step\Worker\ReadDeploymentConfiguration;
use Teknoo\Recipe\Bowl\Bowl;
use Teknoo\Recipe\Bowl\BowlInterface;
use Teknoo\Recipe\Cookbook\BaseCookbookTrait;
use Teknoo\Recipe\RecipeInterface;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class RunJob implements RunJobInterface
{
    use BaseCookbookTrait;

    private DispatchHistoryInterface $stepDispatchHistory;

    private ReceiveJob $stepReceiveJob;

    private DeserializeJob $stepDeserializeJob;

    private PrepareWorkspace $stepPrepareWorkspace;

    private ConfigureCloningAgent $stepConfigureCloningAgent;

    private CloneRepository $stepCloneRepository;

    private ConfigureConductor $stepConfigureConductor;

    private ReadDeploymentConfiguration $stepReadDeploymentConfiguration;

    private CompileDeployment $stepCompileDeployment;

    private HookBuildContainer $stepHookBuildContainer;

    private ConfigureImagesBuilder $stepConfigureImagesBuilder;

    private BuildImages $stepBuildImages;

    private BuildVolumes $stepBuildVolumes;

    private ConfigureClusterClient $stepConfigureClusterClient;

    private Deploying $stepDeploying;

    private Exposing $stepExposing;

    private DispatchResultInterface $stepDispatchResult;

    private DisplayHistory $stepDisplayHistory;

    /**
     * @var iterable<callable>
     */
    private iterable $additionalSteps;

    private DisplayError $stepDisplayError;

    /**
     * @param iterable<callable> $additionalSteps
     */
    public function __construct(
        RecipeInterface $recipe,
        DispatchHistoryInterface $stepDispatchHistory,
        ReceiveJob $stepReceiveJob,
        DeserializeJob $stepDeserializeJob,
        PrepareWorkspace $stepPrepareWorkspace,
        ConfigureCloningAgent $stepConfigureCloningAgent,
        CloneRepository $stepCloneRepository,
        ConfigureConductor $stepConfigureConductor,
        ReadDeploymentConfiguration $stepReadDeploymentConfiguration,
        CompileDeployment $stepCompileDeployment,
        HookBuildContainer $stepHookBuildContainer,
        ConfigureImagesBuilder $stepConfigureImagesBuilder,
        BuildImages $stepBuildImages,
        BuildVolumes $stepBuildVolumes,
        ConfigureClusterClient $stepConfigureClusterClient,
        Deploying $stepDeploying,
        Exposing $stepExposing,
        DispatchResultInterface $stepDispatchResult,
        DisplayHistory $stepDisplayHistory,
        iterable $additionalSteps,
        DisplayError $stepDisplayError
    ) {
        $this->stepDispatchHistory = $stepDispatchHistory;
        $this->stepReceiveJob = $stepReceiveJob;
        $this->stepDeserializeJob = $stepDeserializeJob;
        $this->stepPrepareWorkspace = $stepPrepareWorkspace;
        $this->stepConfigureCloningAgent = $stepConfigureCloningAgent;
        $this->stepCloneRepository = $stepCloneRepository;
        $this->stepConfigureConductor = $stepConfigureConductor;
        $this->stepReadDeploymentConfiguration = $stepReadDeploymentConfiguration;
        $this->stepCompileDeployment = $stepCompileDeployment;
        $this->stepHookBuildContainer = $stepHookBuildContainer;
        $this->stepConfigureImagesBuilder = $stepConfigureImagesBuilder;
        $this->stepBuildImages = $stepBuildImages;
        $this->stepBuildVolumes = $stepBuildVolumes;
        $this->stepConfigureClusterClient = $stepConfigureClusterClient;
        $this->stepDeploying = $stepDeploying;
        $this->stepExposing = $stepExposing;
        $this->stepDispatchResult = $stepDispatchResult;
        $this->stepDisplayHistory = $stepDisplayHistory;
        $this->stepDisplayError = $stepDisplayError;

        $this->additionalSteps = $additionalSteps;

        $this->fill($recipe);
    }


    protected function populateRecipe(RecipeInterface $recipe): RecipeInterface
    {
        $notification = $this->stepDispatchHistory;
        $notificationMapping = ['step' => BowlInterface::METHOD_NAME];

        //Startup Run
        $recipe = $recipe->cook(
            $this->stepReceiveJob,
            ReceiveJob::class,
            [],
            RunJobInterface::STEP_RECEIVE_JOB
        );

        $recipe = $recipe->cook(
            $this->stepDeserializeJob,
            DeserializeJob::class,
            [],
            RunJobInterface::STEP_DESERIALIZE_JOB
        );

        //Prepare workspace
        $recipe = $recipe->cook(
            $notification,
            PrepareWorkspace::class,
            $notificationMapping,
            RunJobInterface::STEP_PREPARE_WORKSPACE
        );
        $recipe = $recipe->cook(
            $this->stepPrepareWorkspace,
            PrepareWorkspace::class,
            [],
            RunJobInterface::STEP_PREPARE_WORKSPACE
        );

        $recipe = $recipe->cook(
            $notification,
            ConfigureCloningAgent::class,
            $notificationMapping,
            RunJobInterface::STEP_CONFIGURE_CLONING_AGENT
        );
        $recipe = $recipe->cook(
            $this->stepConfigureCloningAgent,
            ConfigureCloningAgent::class,
            [],
            RunJobInterface::STEP_CONFIGURE_CLONING_AGENT
        );

        $recipe = $recipe->cook(
            $notification,
            CloneRepository::class,
            $notificationMapping,
            RunJobInterface::STEP_CLONE_REPOSITORY
        );
        $recipe = $recipe->cook(
            $this->stepCloneRepository,
            CloneRepository::class,
            [],
            RunJobInterface::STEP_CLONE_REPOSITORY
        );

        //Configure Deployment
        $recipe = $recipe->cook(
            $notification,
            ConfigureConductor::class,
            $notificationMapping,
            RunJobInterface::STEP_CONFIGURE_CONDUCTOR
        );
        $recipe = $recipe->cook(
            $this->stepConfigureConductor,
            ConfigureConductor::class,
            [],
            RunJobInterface::STEP_CONFIGURE_CONDUCTOR
        );

        $recipe = $recipe->cook(
            $notification,
            ReadDeploymentConfiguration::class,
            $notificationMapping,
            RunJobInterface::STEP_READ_DEPLOYMENT_CONFIGURATION
        );
        $recipe = $recipe->cook(
            $this->stepReadDeploymentConfiguration,
            ReadDeploymentConfiguration::class,
            [],
            RunJobInterface::STEP_READ_DEPLOYMENT_CONFIGURATION
        );

        $recipe = $recipe->cook(
            $notification,
            CompileDeployment::class,
            $notificationMapping,
            RunJobInterface::STEP_COMPILE_DEPLOYMENT
        );
        $recipe = $recipe->cook(
            $this->stepCompileDeployment,
            CompileDeployment::class,
            [],
            RunJobInterface::STEP_COMPILE_DEPLOYMENT
        );

        //Configure Build Image
        $recipe = $recipe->cook(
            $notification,
            HookBuildContainer::class,
            $notificationMapping,
            RunJobInterface::STEP_HOOK_PRE_BUILD_CONTAINER
        );
        $recipe = $recipe->cook(
            $this->stepHookBuildContainer,
            HookBuildContainer::class,
            [],
            RunJobInterface::STEP_HOOK_PRE_BUILD_CONTAINER
        );

        $recipe = $recipe->cook(
            $notification,
            ConfigureImagesBuilder::class,
            $notificationMapping,
            RunJobInterface::STEP_CONNECT_CONTAINER_REPOSITORY
        );
        $recipe = $recipe->cook(
            $this->stepConfigureImagesBuilder,
            ConfigureImagesBuilder::class,
            [],
            RunJobInterface::STEP_CONNECT_CONTAINER_REPOSITORY
        );

        $recipe = $recipe->cook(
            $notification,
            BuildImages::class,
            $notificationMapping,
            RunJobInterface::STEP_BUILD_IMAGE
        );
        $recipe = $recipe->cook(
            $this->stepBuildImages,
            BuildImages::class,
            [],
            RunJobInterface::STEP_BUILD_IMAGE
        );

        $recipe = $recipe->cook(
            $notification,
            BuildVolumes::class,
            $notificationMapping,
            RunJobInterface::STEP_BUILD_VOLUME
        );
        $recipe = $recipe->cook(
            $this->stepBuildVolumes,
            BuildVolumes::class,
            [],
            RunJobInterface::STEP_BUILD_VOLUME
        );

        //Do Deployment
        $recipe = $recipe->cook(
            $notification,
            ConfigureClusterClient::class,
            $notificationMapping,
            RunJobInterface::STEP_CONNECT_MASTER
        );
        $recipe = $recipe->cook(
            $this->stepConfigureClusterClient,
            ConfigureClusterClient::class,
            [],
            RunJobInterface::STEP_CONNECT_MASTER
        );

        $recipe = $recipe->cook(
            $notification,
            Deploying::class,
            $notificationMapping,
            RunJobInterface::STEP_DEPLOYING
        );
        $recipe = $recipe->cook(
            $this->stepDeploying,
            Deploying::class,
            [],
            RunJobInterface::STEP_DEPLOYING
        );

        $recipe = $recipe->cook(
            $notification,
            Exposing::class,
            $notificationMapping,
            RunJobInterface::STEP_EXPOSING
        );
        $recipe = $recipe->cook(
            $this->stepExposing,
            Exposing::class,
            [],
            RunJobInterface::STEP_EXPOSING
        );

        //Final
        $recipe = $recipe->cook(
            $this->stepDispatchResult,
            DispatchResultInterface::class,
            [],
            RunJobInterface::STEP_FINAL
        );

        $recipe = $recipe->cook(
            $this->stepDisplayHistory,
            DisplayHistory::class,
            [],
            RunJobInterface::STEP_FINAL
        );

        foreach ($this->additionalSteps as $position => $step) {
            $recipe = $recipe->cook($step, AdditionalStepsInterface::class, [], $position);
        }

        $recipe = $recipe->onError(new Bowl($this->stepDispatchResult, ['result' => 'exception']));
        $recipe = $recipe->onError(new Bowl($this->stepDisplayError, []));

        return $recipe;
    }
}
