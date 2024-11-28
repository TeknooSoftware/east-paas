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
 * Hook to perform some operations with pip to install dependencies for Python Project.
 * Available composer's commands are :
 * - install
 * - download
 * - wheel
 * - debug
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class PipHook extends AbstractHook
{
    /**
     * @param array{action?: string|null, arguments?: array<string>} $options
     * @return string[]
     * @throws InvalidArgumentException
     */
    protected function validateOptions(array $options): array
    {
        $globalOptions = [
            'isolated',
            'require-virtualenv',
            'trusted-host',
            'cert',
            'client-cert',
            'src',
        ];

        $installOptions = [
            'q',
            'r',
            'no-deps',
            'platform',
            'implementation',
            'upgrade-strategy',
            'force-reinstall',
            'no-clean',
        ];

        $runOptions = [
            'q',
            'r',
            'no-clean',
        ];

        $debugOptions = [
            'platform',
            'implementation',
        ];

        $grantedCommands = [
            'install' => array_merge($globalOptions, $installOptions),
            'download' => array_merge($globalOptions, $installOptions),
            'wheel' => array_merge($globalOptions, $runOptions),
            'debug' => array_merge($globalOptions, $debugOptions),
        ];

        return $this->escapeOptions(
            grantedCommands: $grantedCommands,
            options: $options,
        );
    }
}
