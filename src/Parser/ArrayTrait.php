<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Parser;

use Teknoo\East\Paas\Contracts\Configuration\PropertyAccessorInterface;

trait ArrayTrait
{
    private PropertyAccessorInterface $propertyAccessor;

    private function setPropertyAccessor(PropertyAccessorInterface $propertyAccessor): self
    {
        $this->propertyAccessor = $propertyAccessor;

        return $this;
    }

    /**
     * @param array<string, mixed> $dictionary
     * @param mixed $default
     */
    private function extract(array &$dictionary, string $path, $default, callable $callback): void
    {
        $this->propertyAccessor->getValue(
            $dictionary,
            $path,
            static function ($value) use ($callback, &$default) {
                $callback($value ?? $default);
            },
            $default
        );
    }
}
