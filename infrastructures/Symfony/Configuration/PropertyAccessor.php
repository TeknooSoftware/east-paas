<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony\Configuration;

use Symfony\Component\PropertyAccess\PropertyAccessor as SymfonyPropertyAccessor;
use Teknoo\East\Paas\Contracts\Configuration\PropertyAccessorInterface;

class PropertyAccessor implements PropertyAccessorInterface
{
    private SymfonyPropertyAccessor $propertyAccessor;

    public function __construct(SymfonyPropertyAccessor $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param mixed $value
     */
    public function setValue(array $array, string $propertyPath, $value): PropertyAccessorInterface
    {
        $this->propertyAccessor->setValue($array, $propertyPath, $value);

        return $this;
    }

    /**
     * @param mixed|null $default
     */
    public function getValue(
        array $array,
        string $propertyPath,
        callable $callback,
        $default = null
    ): PropertyAccessorInterface {
        if ($this->propertyAccessor->isReadable($array, $propertyPath)) {
            $callback($this->propertyAccessor->getValue($array, $propertyPath));

            return $this;
        }

        if (null !== $default) {
            $callback($default);
        }

        return $this;
    }
}
