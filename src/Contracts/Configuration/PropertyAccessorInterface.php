<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Contracts\Configuration;

interface PropertyAccessorInterface
{
    /**
     * @param array<string, mixed> $array
     * @param mixed $value
     */
    public function setValue(array $array, string $propertyPath, $value): PropertyAccessorInterface;

    /**
     * @param array<string, mixed> $array
     * @param mixed|null $default
     */
    public function getValue(
        array $array,
        string $propertyPath,
        callable $callback,
        $default = null
    ): PropertyAccessorInterface;
}
