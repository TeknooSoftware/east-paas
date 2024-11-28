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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Git;

use Psr\Container\ContainerInterface;
use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Infrastructures\Git\Contracts\ProcessFactoryInterface;

use function DI\get;

return [
    ProcessFactoryInterface::class => static function (ContainerInterface $container): ProcessFactoryInterface {
        $timeout = 0.0;
        if ($container->has('teknoo.east.paas.git.cloning.timeout')) {
            $timeout = (float) $container->get('teknoo.east.paas.git.cloning.timeout');
        }

        return new class ($timeout) implements ProcessFactoryInterface {
            public function __construct(
                private float $timeout,
            ) {
            }

            public function __invoke(string $commandLine): Process
            {
                $process = Process::fromShellCommandline(
                    'git clone -q --recurse-submodules '
                    . '-b "${:JOB_BRANCH}" "${:JOB_REPOSITORY}" "${:JOB_CLONE_DESTINATION}"'
                );

                $process->setTimeout($this->timeout);

                return $process;
            }
        };
    },

    CloningAgentInterface::class => get(CloningAgent::class),
    CloningAgent::class => static function (ContainerInterface $container): CloningAgent {
        return new CloningAgent(
            $container->get(ProcessFactoryInterface::class),
            'private.key',
        );
    },

    Hook::class => static function (ContainerInterface $container): Hook {
        return new Hook(
            $container->get(ProcessFactoryInterface::class),
            'private.key',
        );
    },
];
