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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Composer;

use Psr\Container\ContainerInterface;
use Symfony\Component\Process\Process;

return [
    ComposerHook::class . ':factory' => static function (ContainerInterface $container): callable {
        return function (array $command, string $cwd) use ($container): Process {
            $process = new Process($command, $cwd);

            $timeout = 0.0;
            if ($container->has('teknoo.east.paas.composer.timeout')) {
                $timeout = (float) $container->get('teknoo.east.paas.composer.timeout');
            }

            $process->setTimeout(
                $timeout,
            );

            return $process;
        };
    },

    ComposerHook::class => static function (ContainerInterface $container): ComposerHook {
        return new ComposerHook(
            $container->get('teknoo.east.paas.composer.phar.path'),
            $container->get(ComposerHook::class . ':factory'),
        );
    },
];
