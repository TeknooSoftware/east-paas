<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
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
