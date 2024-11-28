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
use function pow;
use function ceil;
use function str_ends_with;
use function str_replace;

/**
 * Quota category to manage memory resource, like memory/ram, storage, etc..
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class MemoryAvailability extends AbstractAvailability
{
    protected function stringCapacityToValue(string $capacity): int
    {
        if ($this->isRelative($capacity)) {
            return $this->getRelativeValueFromCapacity((int) $capacity);
        }

        return match (true) {
            str_ends_with($capacity, 'K') => ((int) $capacity) * 1000,
            str_ends_with($capacity, 'M') => ((int) $capacity) * pow(1000, 2),
            str_ends_with($capacity, 'G') => ((int) $capacity) * pow(1000, 3),
            str_ends_with($capacity, 'T') => ((int) $capacity) * pow(1000, 4),
            str_ends_with($capacity, 'P') => ((int) $capacity) * pow(1000, 5),
            str_ends_with($capacity, 'E') => ((int) $capacity) * pow(1000, 6),
            str_ends_with($capacity, 'Ki') => ((int) $capacity) * 1024,
            str_ends_with($capacity, 'Mi') => ((int) $capacity) * pow(1024, 2),
            str_ends_with($capacity, 'Gi') => ((int) $capacity) * pow(1024, 3),
            str_ends_with($capacity, 'Ti') => ((int) $capacity) * pow(1024, 4),
            str_ends_with($capacity, 'Pi') => ((int) $capacity) * pow(1024, 5),
            str_ends_with($capacity, 'Ei') => ((int) $capacity) * pow(1024, 6),
            default => (int) $capacity,
        };
    }

    protected function valueToStringCapacity(int $value): string
    {
        $format = fn (float $value): string => str_replace('.000', '', number_format($value, 3, '.', ''));

        return match (true) {
            $value < 1024 => (string) $value,
            1024 <= $value && $value < pow(1024, 2) => (string) ceil($value / 1024) . 'Ki',
            pow(1024, 2) <= $value && $value < pow(1024, 3) => (string) $format($value / pow(1024, 2)) . 'Mi',
            pow(1024, 3) <= $value && $value < pow(1024, 4) => (string) $format($value / pow(1024, 3)) . 'Gi',
            pow(1024, 4) <= $value && $value < pow(1024, 5) => (string) $format($value / pow(1024, 4)) . 'Ti',
            pow(1024, 5) <= $value && $value < pow(1024, 6) => (string) $format($value / pow(1024, 5)) . 'Pi',
            pow(1024, 6) <= $value => (string) ceil($value / pow(1024, 6)) . 'Ei',
        };
    }
}
