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

namespace Teknoo\East\Paas\Infrastructures\ProjectBuilding;

use Teknoo\East\Paas\Infrastructures\ProjectBuilding\Exception\InvalidArgumentException;

use function array_merge;

/**
 * Hook to perform some operations with composer to install dependencies for PHP Project.
 * Available composer's commands are :
 * - dump-autoload
 * - dumpautoload
 * - exec
 * - install
 * - require
 * - run
 * - run-script
 * - update
 * - upgrade
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ComposerHook extends AbstractHook
{
    /**
     * @param array{action?: string|null, arguments?: array<string>} $options
     * @return string[]
     * @throws InvalidArgumentException
     */
    protected function validateOptions(array $options): array
    {
        $globalOptions = [
            'quiet',
            'version',
            'ansi',
            'dev',
            'no-dev',
            'no-ansi',
            'no-interaction',
            'no-plugins',
            'no-scripts',
            'working-dir',
            'no-cache'
        ];

        $dumpOptions = [
            'optimize',
            'classmap-authoritative',
            'apcu',
            'ignore-platform-req',
            'ignore-platform-reqs',
            'strict-psr',
        ];

        $installOptions = [
            'prefer-source',
            'prefer-dist',
            'prefer-install',
            'dry-run',
            'no-suggest',
            'no-autoloader',
            'no-progress',
            'no-install',
            'audit',
            'optimize-autoloader',
        ];

        $runOptions = [];

        $requireOptions = [
            'update-with-dependencies',
            'update-with-all-dependencies',
            'with-dependencies',
            'with-all-dependencies',
        ];

        $grantedCommands = [
            'dump-autoload' => array_merge($globalOptions, $dumpOptions),
            'dumpautoload' => array_merge($globalOptions, $dumpOptions),
            'exec' => array_merge($globalOptions, $runOptions),
            'install' => array_merge($globalOptions, $dumpOptions, $installOptions),
            'require' => array_merge($globalOptions, $dumpOptions, $installOptions, $requireOptions),
            'run' => array_merge($globalOptions, $runOptions),
            'run-script' => array_merge($globalOptions, $runOptions),
            'update' => array_merge($globalOptions, $dumpOptions, $installOptions),
            'upgrade' => array_merge($globalOptions, $dumpOptions, $installOptions),
        ];

        return $this->escapeOptions(
            grantedCommands: $grantedCommands,
            options: $options,
        );
    }
}
