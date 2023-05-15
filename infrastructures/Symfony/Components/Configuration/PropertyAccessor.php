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

namespace Teknoo\East\Paas\Infrastructures\Symfony\Configuration;

use Symfony\Component\PropertyAccess\PropertyAccessor as SymfonyPropertyAccessor;
use Teknoo\East\Paas\Contracts\Configuration\PropertyAccessorInterface;

/**
 * Service, built on PropertyAccessor of Symfony, able to parse a path of key' split by a dot, to read / pass the
 * value of a multidimensional array to a callable.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class PropertyAccessor implements PropertyAccessorInterface
{
    public function __construct(
        private readonly SymfonyPropertyAccessor $propertyAccessor,
    ) {
    }

    public function setValue(array &$array, string $propertyPath, mixed $value): PropertyAccessorInterface
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
