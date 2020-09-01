<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message;

use Symfony\Component\Messenger\Stamp\StampInterface;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

class Parameter implements ImmutableInterface, StampInterface
{
    use ImmutableTrait;

    private string $name;

    private string $value;

    public function __construct(string $name, string $value)
    {
        $this->uniqueConstructorCheck();

        $this->name = $name;
        $this->value = $value;
    }

    public function getName(): string
    {
        return '%' . $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
