<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Cluster;

use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;
use Teknoo\East\Paas\Contracts\Cluster\ClientInterface;

class Collection implements \IteratorAggregate, ImmutableInterface
{
    use ImmutableTrait;

    /**
     * @var iterable<int, ClientInterface>
     */
    private iterable $clients;

    /**
     * @param iterable<int, ClientInterface> $clients
     */
    public function __construct(iterable $clients)
    {
        $this->uniqueConstructorCheck();

        $this->clients = $clients;
    }

    /**
     * @return \Generator|\Traversable<int, ClientInterface>
     */
    public function getIterator()
    {
        yield from $this->clients;
    }
}
