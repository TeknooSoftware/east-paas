<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
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
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Contracts\Recipe\Plan;

use Teknoo\Recipe\EditablePlanInterface;

/**
 * Plan to run a created job via the plan NewJob, aka a project deployment.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
interface RunJobInterface extends EditablePlanInterface
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
