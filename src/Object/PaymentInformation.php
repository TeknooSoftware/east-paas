<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Object;

use Teknoo\East\Website\Object\ObjectInterface;
use Teknoo\East\Website\Object\ObjectTrait;
use Teknoo\East\Website\Object\TimestampableInterface;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

class PaymentInformation implements ObjectInterface, ImmutableInterface, TimestampableInterface
{
    use ObjectTrait;
    use ImmutableTrait;

    private ?string $cardHash = null;

    public function __construct(string $cardHash = '')
    {
        $this->uniqueConstructorCheck();

        $this->cardHash = $cardHash;
    }

    public function getCardHash(): string
    {
        return (string) $this->cardHash;
    }
}
