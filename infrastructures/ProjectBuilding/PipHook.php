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

use RuntimeException;
use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\Exception\InvalidArgumentException;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Throwable;

use function array_merge;
use function preg_match;
use function reset;

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
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class PipHook extends AbstractHook
{
    /**
     * @param array{action?: string|null, arguments?: iterable<string>} $options
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
            '[a-zA-Z0-9\-_"/]+',
            'r',
            'no-deps',
            'platform',
            'implementation',
            'upgrade-strategy',
            'force-reinstall',
            'no-clean',
        ];

        $runOptions = [
            '[a-zA-Z0-9\-_"/]+',
            'r',
            'no-clean',
        ];

        $debugOptions = [
            '[a-zA-Z0-9\-_"/]+',
            'platform',
            'implementation',
        ];

        $grantedCommands = [
            'install' => array_merge($globalOptions, $installOptions),
            'download' => array_merge($globalOptions, $installOptions),
            'wheel' => array_merge($globalOptions, $runOptions),
            'debug' => array_merge($globalOptions, $debugOptions),
        ];

        $args = [];
        if (!isset($options['action'])) {
            $cmd = (string) reset($options);
        } else {
            $cmd = $options['action'];
            $args = $options['arguments'] ?? [];
        }

        foreach ([$cmd, ...$args] as &$value) {
            if (!is_scalar($value)) {
                throw new InvalidArgumentException('composer action and arguments must be scalars values');
            }

            if (preg_match('#[\&\|<>;]#S', (string) $value)) {
                throw new InvalidArgumentException('Pipe and redirection are forbidden');
            }
        }

        if (!isset($grantedCommands[$cmd])) {
            throw new InvalidArgumentException("$cmd is forbidden");
        }

        $final = [$cmd];
        foreach ($args as &$arg) {
            $pattern = '#^' . implode('|', $grantedCommands[$cmd]) . '$#S';
            if (!preg_match($pattern, (string) $arg)) {
                throw new InvalidArgumentException("$arg is not a granted option for $cmd");
            }

            $final[] = '--' . $arg;
        }

        return $final;
    }
}
