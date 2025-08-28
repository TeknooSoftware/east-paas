<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\ProjectBuilding;

use Teknoo\East\Paas\Infrastructures\ProjectBuilding\Exception\InvalidArgumentException;

use function preg_match;
use function reset;

/**
 * Hook to perform some operations with make tools
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class MakeHook extends AbstractHook
{
    /**
     * @param array{action?: string|null, arguments?: iterable<string>} $options
     * @return string[]
     * @throws InvalidArgumentException
     */
    protected function validateOptions(array $options): array
    {
        $args = [];
        if (!isset($options['action'])) {
            $cmd = (string) reset($options);
        } else {
            $cmd = $options['action'];
            $args = $options['arguments'] ?? [];
        }

        $cmds = [$cmd, ...$args];
        foreach ($cmds as &$value) {
            if (!is_scalar($value)) {
                throw new InvalidArgumentException('composer action and arguments must be scalars values');
            }

            if (preg_match('#[\&\|<>;]#S', (string) $value)) {
                throw new InvalidArgumentException('Pipe and redirection are forbidden');
            }

            $value = (string) $value;
        }

        /** @var string[] $cmds */
        return $cmds;
    }
}
