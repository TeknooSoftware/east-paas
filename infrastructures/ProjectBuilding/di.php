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

namespace Teknoo\East\Paas\Infrastructures\ProjectBuilding;

use ArrayObject;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\Exception\RuntimeException;
use Psr\Container\ContainerInterface;
use Symfony\Component\Process\Process;

use function function_exists;
use function implode;
use function is_string;
use function trigger_error;

use const E_USER_DEPRECATED;

if (!function_exists(__NAMESPACE__ . '\getArrayFromValue')) {
    /**
     * @param string|array<int, string>|ArrayObject<int, string> $value
     * @return string[]
     */
// phpcs:disable
    function getArrayFromValue(string|array|ArrayObject $value): array
    {
        if ($value instanceof ArrayObject) {
            return $value->getArrayCopy();
        }

        if (is_string($value)) {
            return [$value];
        }

        return $value;
    }
// phpcs:enable
}

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
        if ($container->has('teknoo.east.paas.composer.path')) {
            $binaryPath = $container->get('teknoo.east.paas.composer.path');
        } elseif ($container->has('teknoo.east.paas.composer.phar.path')) {
            @trigger_error(
                "'teknoo.east.paas.composer.phar.path' is deprecated, use 'teknoo.east.paas.composer.path' instead",
                E_USER_DEPRECATED
            );
            $binaryPath = $container->get('teknoo.east.paas.composer.phar.path');
        } else {
            throw new RuntimeException("'teknoo.east.paas.composer.phar.path' is missed in DI parameter");
        }

        return new ComposerHook(
            getArrayFromValue($binaryPath),
            $container->get(ComposerHook::class . ':factory'),
        );
    },

    MakeHook::class . ':factory' => static function (ContainerInterface $container): callable {
        return function (array $command, string $cwd) use ($container): Process {
            $process = new Process($command, $cwd);

            $timeout = 0.0;
            if ($container->has('teknoo.east.paas.make.timeout')) {
                $timeout = (float) $container->get('teknoo.east.paas.make.timeout');
            }

            $process->setTimeout(
                $timeout,
            );

            return $process;
        };
    },

    MakeHook::class => static function (ContainerInterface $container): MakeHook {
        return new MakeHook(
            getArrayFromValue($container->get('teknoo.east.paas.make.path')),
            $container->get(MakeHook::class . ':factory'),
        );
    },

    NpmHook::class . ':factory' => static function (ContainerInterface $container): callable {
        return function (array $command, string $cwd) use ($container): Process {
            $process = new Process($command, $cwd);

            $timeout = 0.0;
            if ($container->has('teknoo.east.paas.npm.timeout')) {
                $timeout = (float) $container->get('teknoo.east.paas.npm.timeout');
            }

            $process->setTimeout(
                $timeout,
            );

            return $process;
        };
    },

    NpmHook::class => static function (ContainerInterface $container): NpmHook {
        return new NpmHook(
            getArrayFromValue($container->get('teknoo.east.paas.npm.path')),
            $container->get(NpmHook::class . ':factory'),
        );
    },

    PipHook::class . ':factory' => static function (ContainerInterface $container): callable {
        return function (array $command, string $cwd) use ($container): Process {
            $process = Process::fromShellCommandline(
                command: implode(
                    ' ',
                    [
                        'virtualenv --copies --clear -p python3 venv && . venv/bin/activate && ',
                        ...$command,
                    ],
                ),
                cwd: $cwd,
            );

            $timeout = 0.0;
            if ($container->has('teknoo.east.paas.pip.timeout')) {
                $timeout = (float) $container->get('teknoo.east.paas.pip.timeout');
            }

            $process->setTimeout(
                $timeout,
            );

            return $process;
        };
    },

    PipHook::class => static function (ContainerInterface $container): PipHook {
        return new PipHook(
            getArrayFromValue($container->get('teknoo.east.paas.pip.path')),
            $container->get(PipHook::class . ':factory'),
        );
    },
];
