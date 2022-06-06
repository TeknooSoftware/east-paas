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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Git;

use Gitonomy\Git\Admin;
use Psr\Container\ContainerInterface;
use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;

use function DI\get;

return [
    CloningAgentInterface::class => get(CloningAgent::class),
    CloningAgent::class => static function (ContainerInterface $container): CloningAgent {
        return new CloningAgent(
            Process::fromShellCommandline(
                'git clone -q --recurse-submodules -b "${:JOB_BRANCH}" "${:JOB_REPOSITORY}" "${:JOB_CLONE_DESTINATION}"'
            ),
            'private.key',
        );
    },

    Hook::class => static function (ContainerInterface $container): Hook {
        return new Hook(
            Process::fromShellCommandline(
                'git clone -q --recurse-submodules -b "${:JOB_BRANCH}" "${:JOB_REPOSITORY}" "${:JOB_CLONE_DESTINATION}"'
            ),
            'private.key',
        );
    },
];
