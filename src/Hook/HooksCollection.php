<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Hook;

use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\East\Paas\Contracts\Hook\HooksCollectionInterface;

class HooksCollection implements HooksCollectionInterface, ImmutableInterface
{
    use ImmutableTrait;

    /**
     * @var array<string, HookInterface>
     */
    private array $hooks;

    /**
     * @param array<string, HookInterface> $hooks
     */
    public function __construct(array $hooks)
    {
        $this->uniqueConstructorCheck();

        $this->hooks = $hooks;
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->hooks as $name => $hook) {
            yield $name => $hook;
        }
    }
}
