<?php

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

declare(strict_types=1);

namespace Teknoo\East\Paas\Object;

use JsonSerializable;

/**
 * DTO Object to manage account's quota in a Symfony Doctrine Form Type
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class AccountQuota implements JsonSerializable
{
    public function __construct(
        public string $category = '',
        public string $type = '',
        public string $capacity = '',
        public string $requires = '',
    ) {
    }

    public function getRequires(): string
    {
        if (empty($this->requires)) {
            return $this->capacity;
        }

        return $this->requires;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'category' => $this->category,
            'type' => $this->type,
            'capacity' => $this->capacity,
            'requires' => $this->getRequires(),
        ];
    }

    /**
     * @param array{category: string, type: string, capacity: string, requires: string} $values
     */
    public static function create(array $values): self
    {
        return new self(
            category: $values['category'],
            type: $values['type'],
            capacity: $values['capacity'],
            requires: (string) ($values['requires'] ?? null),
        );
    }
}
