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
use Teknoo\East\Paas\Contracts\Recipe\Cookbook\RunJobInterface;
use Teknoo\East\Paas\Recipe\Step\History\DisplayHistory;
use Teknoo\East\Paas\Recipe\Step\History\SendHistory;
use Teknoo\East\Paas\Recipe\Step\Job\DeserializeJob;
use Teknoo\East\Paas\Recipe\Step\Job\ReceiveJob;
use Teknoo\East\Paas\Recipe\Step\Misc\DisplayError;
use Teknoo\East\Paas\Recipe\Step\Misc\PushResult;
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
use Teknoo\Recipe\BaseRecipeInterface;
use Teknoo\Recipe\Bowl\Bowl;
use Teknoo\Recipe\Bowl\BowlInterface;
use Teknoo\Recipe\ChefInterface;
use Teknoo\Recipe\CookbookInterface;
use Teknoo\Recipe\Ingredient\Ingredient;
use Teknoo\Recipe\RecipeInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class RunJob implements RunJobInterface
{
    private RecipeInterface $recipe;

    private bool $recipePopulated = false;

    protected function populateRecipe(RecipeInterface $recipe): RecipeInterface
    {
        $recipe = $recipe->require(new Ingredient(ServerRequestInterface::class, 'request'));

        $notification = $container->get(SendHistory::class);
        $notificationMapping = ['step' => BowlInterface::METHOD_NAME];

        //Startup Run
        $recipe = $recipe->cook(
            $container->get(ReceiveJob::class),
            ReceiveJob::class,
            [],
            RunJobInterface::STEP_RECEIVE_JOB
        );

        $recipe = $recipe->cook(
            $container->get(DeserializeJob::class),
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
            $container->get(PrepareWorkspace::class),
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
            $container->get(ConfigureCloningAgent::class),
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
            $container->get(CloneRepository::class),
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
            $container->get(ConfigureConductor::class),
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
            $container->get(ReadDeploymentConfiguration::class),
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
            $container->get(CompileDeployment::class),
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
            $container->get(HookBuildContainer::class),
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
            $container->get(ConfigureImagesBuilder::class),
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
            $container->get(BuildImages::class),
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
            $container->get(BuildVolumes::class),
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
            $container->get(ConfigureClusterClient::class),
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
            $container->get(Deploying::class),
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
            $container->get(Exposing::class),
            Exposing::class,
            [],
            RunJobInterface::STEP_EXPOSING
        );

        //Final
        $recipe = $recipe->cook(
            $container->get(PushResult::class),
            PushResult::class,
            [],
            RunJobInterface::STEP_FINAL
        );

        $recipe = $recipe->cook(
            $container->get(DisplayHistory::class),
            DisplayHistory::class,
            [],
            RunJobInterface::STEP_FINAL
        );

        $recipe = $recipe->onError(new Bowl($container->get(PushResult::class), ['result' => 'exception']));
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
