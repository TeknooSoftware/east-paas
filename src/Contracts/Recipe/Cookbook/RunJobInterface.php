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

namespace Teknoo\East\Paas\Contracts\Recipe\Cookbook;

use Teknoo\Recipe\CookbookInterface;

/**
 * Cookbook to run a created job via the cookbook NewJob, aka a project deployment.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
interface RunJobInterface extends CookbookInterface
{
    //Startup Run
    final public const STEP_CONFIG_PING = 5;
    final public const STEP_SET_TIMEOUT = 6;
    final public const STEP_RECEIVE_JOB = 10;
    final public const STEP_DESERIALIZE_JOB = 20;

    //Prepare workspace
    final public const STEP_PREPARE_WORKSPACE = 30;
    final public const STEP_CONFIGURE_CLONING_AGENT = 40;
    final public const STEP_CLONE_REPOSITORY = 50;

    //Configure Deployment
    final public const STEP_CONFIGURE_CONDUCTOR = 60;
    final public const STEP_READ_DEPLOYMENT_CONFIGURATION = 70;
    final public const STEP_COMPILE_DEPLOYMENT = 80;

    //Configure Build Image
    final public const STEP_HOOK_PRE_BUILD_CONTAINER = 90;
    final public const STEP_CONNECT_CONTAINER_REPOSITORY = 100;
    final public const STEP_BUILD_IMAGE = 110;
    final public const STEP_BUILD_VOLUME = 120;

    //Do Deployment
    final public const STEP_CONNECT_MASTER = 130;
    final public const STEP_DEPLOYING = 140;
    final public const STEP_EXPOSING = 150;

    final public const STEP_FINAL = 160;
    final public const STEP_SEND_HISTORY = 170;
    final public const STEP_UNSET_TIMEOUT = 180;
}
