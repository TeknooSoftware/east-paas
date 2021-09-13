<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Compilation\CompiledDeployment;

use Generator;
use IteratorAggregate;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

/**
 * Immutable value object, representing a normalized Pod, depoyable unit, grouping at least one
 * container, having shared storage / network, and a specification on how to run these containers. The elements of a
 * pod are always co-located and co-ordered, and run in a shared context.
 * Pods can being replicated more than once times.
 *
 * @implements IteratorAggregate<Container>
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Pod implements ImmutableInterface, IteratorAggregate
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
     * @return iterable<Container>
     */
    public function getIterator(): iterable
    {
        yield from $this->containers;
    }
}
