<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Contracts\Object;

use Teknoo\Immutable\ImmutableInterface;

interface IdentityInterface extends ImmutableInterface
{
    public function getName(): string;

    public function __toString(): string;
}
