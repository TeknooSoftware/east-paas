<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony\Configuration;

use Symfony\Component\PropertyAccess\PropertyAccessor as SymfonyPropertyAccessor;
use Teknoo\East\Paas\Contracts\Configuration\PropertyAccessorInterface;

/**
 * Service, built on PropertyAccessor of Symfony, able to parse a path of key' split by a dot, to read / pass the
 * value of a multidimensional array to a callable.
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class PropertyAccessor implements PropertyAccessorInterface
{
    public function __construct(
        private SymfonyPropertyAccessor $propertyAccessor,
    ) {
    }

    public function setValue(array $array, string $propertyPath, mixed $value): PropertyAccessorInterface
    {
        $this->propertyAccessor->setValue($array, $propertyPath, $value);

        return $this;
    }

    public function getValue(
        array $array,
        string $propertyPath,
        callable $callback,
        mixed $default = null
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
