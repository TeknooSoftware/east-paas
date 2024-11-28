<?php

declare(strict_types=1);

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

namespace Teknoo\East\Paas\Compilation\Compiler\Quota;

use function number_format;
use function str_ends_with;

/**
 * Quota categories for all compute resource, like a cpu, a GPU, etc
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ComputeAvailability extends AbstractAvailability
{
    private const MILLI_COEF = 1000;

    protected function stringCapacityToValue(string $capacity): int
    {
        if ($this->isRelative($capacity)) {
            return $this->getRelativeValueFromCapacity((int) $capacity);
        }

        if (str_ends_with($capacity, 'm')) {
            return (int) $capacity;
        }

        return (int) (((float) $capacity) * self::MILLI_COEF);
    }

    protected function valueToStringCapacity(int $value): string
    {
        return match (true) {
            $value < self::MILLI_COEF => $value . 'm',
            default => number_format(
                num: ($value / self::MILLI_COEF),
                decimals: 3,
                decimal_separator: '.',
                thousands_separator: '',
            ),
        };
    }
}
