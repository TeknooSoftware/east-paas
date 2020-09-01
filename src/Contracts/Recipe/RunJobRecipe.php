<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Contracts\Recipe;

use Teknoo\Recipe\RecipeInterface;

interface RunJobRecipe extends RecipeInterface
{
    //Startup Run
    public const STEP_RECEIVE_JOB = 10;
    public const STEP_DESERIALIZE_JOB = 20;

    //Prepare workspace
    public const STEP_PREPARE_WORKSPACE = 30;
    public const STEP_CONFIGURE_CLONING_AGENT = 40;
    public const STEP_CLONE_REPOSITORY = 50;
    public const STEP_CLONE_REPOSITORY_SUBMODULE = 60;

    //Configure Deployment
    public const STEP_CONFIGURE_CONDUCTOR = 60;
    public const STEP_READ_DEPLOYMENT_CONFIGURATION = 70;
    public const STEP_COMPILE_DEPLOYMENT = 80;

    //Configure Build Image
    public const STEP_HOOK_PRE_BUILD_CONTAINER = 90;
    public const STEP_CONNECT_CONTAINER_REPOSITORY = 100;
    public const STEP_BUILD_IMAGE = 110;
    public const STEP_BUILD_VOLUME = 120;

    //Do Deployment
    public const STEP_CONNECT_MASTER = 130;
    public const STEP_DEPLOYING = 140;
    public const STEP_EXPOSING = 150;

    public const STEP_FINAL = 160;
}
