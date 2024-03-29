<?php

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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Git;

use Psr\Container\ContainerInterface;
use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;

use function DI\get;

return [
    CloningAgentInterface::class => get(CloningAgent::class),
    CloningAgent::class => static function (ContainerInterface $container): CloningAgent {
        $process = Process::fromShellCommandline(
            'git clone -q --recurse-submodules -b "${:JOB_BRANCH}" "${:JOB_REPOSITORY}" "${:JOB_CLONE_DESTINATION}"'
        );

        $timeout = 0.0;
        if ($container->has('teknoo.east.paas.git.cloning.timeout')) {
            $timeout = (float) $container->get('teknoo.east.paas.git.cloning.timeout');
        }

        $process->setTimeout(
            $timeout,
        );

        return new CloningAgent(
            $process,
            'private.key',
        );
    },

    Hook::class => static function (ContainerInterface $container): Hook {
        $process = Process::fromShellCommandline(
            'git clone -q --recurse-submodules -b "${:JOB_BRANCH}" "${:JOB_REPOSITORY}" "${:JOB_CLONE_DESTINATION}"'
        );

        $timeout = 0.0;
        if ($container->has('teknoo.east.paas.git.cloning.timeout')) {
            $timeout = (float) $container->get('teknoo.east.paas.git.cloning.timeout');
        }

        $process->setTimeout(
            $timeout,
        );

        return new Hook(
            $process,
            'private.key',
        );
    },
];
