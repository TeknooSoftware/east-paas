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

namespace Teknoo\East\Paas\Cluster;

use IteratorAggregate;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface;

/**
 * Immutable and iterable collections of cluster's drivers (adapter to use to connect to a cluster,
 * like Kubernetes or Docker Swarm)
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Collection implements IteratorAggregate, ImmutableInterface
{
    use ImmutableTrait;

    /**
     * @var iterable<int, DriverInterface>
     */
    private iterable $clients;

    /**
     * @param iterable<int, DriverInterface> $clients
     */
    public function __construct(iterable $clients)
    {
        $this->uniqueConstructorCheck();

        $this->clients = $clients;
    }

    /**
     * @return iterable<DriverInterface>
     */
    public function getIterator(): iterable
    {
        yield from $this->clients;
    }
}
