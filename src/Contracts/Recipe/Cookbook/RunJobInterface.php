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

namespace Teknoo\East\Paas\Contracts\Recipe\Cookbook;

use Teknoo\Recipe\CookbookInterface;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
interface RunJobInterface extends CookbookInterface
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
