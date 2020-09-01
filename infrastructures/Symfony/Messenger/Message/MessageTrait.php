<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message;

use Teknoo\Immutable\ImmutableTrait;

trait MessageTrait
{
    use ImmutableTrait;

    private string $message;

    public function __construct(string $message)
    {
        $this->uniqueConstructorCheck();

        $this->message = $message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function __toString()
    {
        return $this->message;
    }
}
