<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Cluster;

use IteratorAggregate;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface;
use Traversable;

/**
 * Immutable and iterable collections of cluster's drivers (adapter to use to connect to a cluster,
 * like Kubernetes or Docker Swarm)
 *
 * @implements IteratorAggregate<DriverInterface>
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Collection implements IteratorAggregate, ImmutableInterface
{
    use ImmutableTrait;

    /**
     * @param iterable<int, DriverInterface> $clients
     */
    public function __construct(
        private readonly iterable $clients
    ) {
        $this->uniqueConstructorCheck();
    }

    /**
     * @return Traversable<DriverInterface>
     */
    public function getIterator(): Traversable
    {
        yield from $this->clients;
    }
}
