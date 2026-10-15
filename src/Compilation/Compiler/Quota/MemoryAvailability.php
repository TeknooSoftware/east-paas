<?php

declare(strict_types=1);

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

namespace Teknoo\East\Paas\Compilation\Compiler\Quota;

use function intdiv;
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class MemoryAvailability extends AbstractAvailability
{
    /**
     * Binary units, from the largest, to write a value in the largest unit where it is whole
     */
    private const array WHOLE_UNITS = [
        'Ei' => 1024 ** 6,
        'Pi' => 1024 ** 5,
        'Ti' => 1024 ** 4,
        'Gi' => 1024 ** 3,
        'Mi' => 1024 ** 2,
        'Ki' => 1024,
    ];

    protected function stringCapacityToValue(string $capacity): int
    {
        if ($this->isRelative($capacity)) {
            return $this->getRelativeValueFromCapacity((int) $capacity);
        }

        return match (true) {
            str_ends_with($capacity, 'K') => $this->quantityToValue($capacity, base: 1000, power: 1),
            str_ends_with($capacity, 'M') => $this->quantityToValue($capacity, base: 1000, power: 2),
            str_ends_with($capacity, 'G') => $this->quantityToValue($capacity, base: 1000, power: 3),
            str_ends_with($capacity, 'T') => $this->quantityToValue($capacity, base: 1000, power: 4),
            str_ends_with($capacity, 'P') => $this->quantityToValue($capacity, base: 1000, power: 5),
            str_ends_with($capacity, 'E') => $this->quantityToValue($capacity, base: 1000, power: 6),
            str_ends_with($capacity, 'Ki') => $this->quantityToValue($capacity, base: 1024, power: 1),
            str_ends_with($capacity, 'Mi') => $this->quantityToValue($capacity, base: 1024, power: 2),
            str_ends_with($capacity, 'Gi') => $this->quantityToValue($capacity, base: 1024, power: 3),
            str_ends_with($capacity, 'Ti') => $this->quantityToValue($capacity, base: 1024, power: 4),
            str_ends_with($capacity, 'Pi') => $this->quantityToValue($capacity, base: 1024, power: 5),
            str_ends_with($capacity, 'Ei') => $this->quantityToValue($capacity, base: 1024, power: 6),
            default => $this->quantityToValue($capacity),
        };
    }

    protected function valueToStringCapacity(int $value): string
    {
        $format = fn (float $value): string => str_replace('.000', '', number_format($value, 3, '.', ''));

        //Kubernetes must read exactly the value: a notation with 3 decimals is exact only for a whole number of
        //thousandths of the unit, so a multiple of an eighth of the unit (1000 = 8 * 125), a rounded up notation only
        //for a whole number of the unit. Otherwise, the value is written in the largest unit where it is whole, or in
        //bytes
        $isExact = fn (int $divisor): bool => 0 === $value % $divisor;

        $capacity = match (true) {
            $value < 1024 => (string) $value,
            1024 <= $value && $value < 1024 ** 2 && $isExact(1024) => ceil($value / 1024) . 'Ki',
            1024 ** 2 <= $value && $value < 1024 ** 3 && $isExact(1024 ** 2 / 8) => $format($value / 1024 ** 2) . 'Mi',
            1024 ** 3 <= $value && $value < 1024 ** 4 && $isExact(1024 ** 3 / 8) => $format($value / 1024 ** 3) . 'Gi',
            1024 ** 4 <= $value && $value < 1024 ** 5 && $isExact(1024 ** 4 / 8) => $format($value / 1024 ** 4) . 'Ti',
            1024 ** 5 <= $value && $value < 1024 ** 6 && $isExact(1024 ** 5 / 8) => $format($value / 1024 ** 5) . 'Pi',
            1024 ** 6 <= $value && $isExact(1024 ** 6) => ceil($value / 1024 ** 6) . 'Ei',
            default => null,
        };

        if (null !== $capacity) {
            return $capacity;
        }

        foreach (self::WHOLE_UNITS as $suffix => $unit) {
            if (0 === $value % $unit) {
                return intdiv($value, $unit) . $suffix;
            }
        }

        return (string) $value;
    }
}
