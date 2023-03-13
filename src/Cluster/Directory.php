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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Cluster;

use DomainException;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface;
use Teknoo\East\Paas\Object\Cluster;

/**
 * Cluster's drivers directory able to find and configure a Cluster instance with a driver corresponding with the type
 * required.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/states Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Directory
{
    /**
     * @var array<string, DriverInterface>
     */
    private array $clients = [];

    public function register(string $type, DriverInterface $client): self
    {
        $this->clients[$type] = $client;

        return $this;
    }

    /**
     * @param PromiseInterface<DriverInterface, mixed> $promise
     */
    public function require(string $type, Cluster $cluster, PromiseInterface $promise): self
    {
        if (!isset($this->clients[$type])) {
            $promise->fail(new DomainException("No available client for $type", 500));

            return $this;
        }

        $cluster->configureCluster($this->clients[$type], $promise);

        return $this;
    }
}
