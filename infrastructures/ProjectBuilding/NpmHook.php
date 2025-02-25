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
 * Hook to perform some operations with npm to install dependencies for Javascript Project.
 * Available composer's commands are :
 * - login
 * - install
 * - update
 * - test
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class NpmHook extends AbstractHook
{
    /**
     * @param array{action?: string|null, arguments?: array<string>} $options
     * @return string[]
     * @throws InvalidArgumentException
     */
    protected function validateOptions(array $options): array
    {
        $globalOptions = [
            'ignore-scripts',
        ];

        $installOptions = [
            'save',
            'no-save',
            'save-prod',
            'save-optional',
            'install-strategy',
            'legacy-bundling',
            'omit',
            'strict-peer-deps',
            'no-package-lock',
            'foreground-scripts',
            'ignore-scripts',
            'no-audit',
            'no-fund',
            'dry-run',
            'workspace',
            'workspaces',
            'include-workspace-root',
            'no-install-links',
        ];

        $grantedCommands = [
            'login' => [
                'registry',
                'scope',
                'auth-type',
            ],
            'install' => array_merge($globalOptions, $installOptions),
            'update' => array_merge($globalOptions, $installOptions),
            'test' => array_merge($globalOptions, ['script-shell']),
        ];

        return $this->escapeOptions(
            grantedCommands: $grantedCommands,
            options: $options,
        );
    }
}
