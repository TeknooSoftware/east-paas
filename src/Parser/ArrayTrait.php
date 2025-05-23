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

namespace Teknoo\East\Paas\Parser;

use Teknoo\East\Paas\Contracts\Configuration\PropertyAccessorInterface;

/**
 * Trait to use a PropertyAccessorInterface instance on an array to get a value or return a default value.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
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
     */
    private function extract(array &$dictionary, string $path, mixed $default, callable $callback): void
    {
        $this->propertyAccessor->getValue(
            $dictionary,
            $path,
            static function ($value) use ($callback, &$default): void {
                $callback($value ?? $default);
            },
            $default
        );
    }

    /**
     * @param array<string, mixed> $dictionary
     */
    private function replace(array &$dictionary, string $path, mixed $value): void
    {
        $this->propertyAccessor->setValue(
            $dictionary,
            $path,
            $value,
        );
    }
}
