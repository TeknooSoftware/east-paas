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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Contracts\Configuration;

/**
 * To define service able to parse a path of key' split by a dot, to read / pass the value of a
 * multidimensional array to a callable
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
interface PropertyAccessorInterface
{
    /**
     * @param array<string, mixed> $array
     */
    public function setValue(array &$array, string $propertyPath, mixed $value): PropertyAccessorInterface;

    /**
     * @param array<string, mixed> $array
     */
    public function getValue(
        array $array,
        string $propertyPath,
        callable $callback,
        mixed $default = null
    ): PropertyAccessorInterface;
}
