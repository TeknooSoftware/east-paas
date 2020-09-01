<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Container;

use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

class Pod implements ImmutableInterface, \IteratorAggregate
{
    use ImmutableTrait;

    private string $name;

    private int $replicas;

    /**
     * @var array<int, Container>
     */
    private array $containers;

    /**
     * @param array<int, Container> $containers
     */
    public function __construct(string $name, int $replicas, array $containers)
    {
        $this->uniqueConstructorCheck();

        $this->name = $name;
        $this->replicas = $replicas;
        $this->containers = $containers;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getReplicas(): int
    {
        return $this->replicas;
    }

    /**
     * @return \Generator<Container>|array<int, Container>
     */
    public function getIterator()
    {
        yield from $this->containers;
    }
}
