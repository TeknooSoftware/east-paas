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
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Cluster;

use DomainException;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * Cluster's drivers directory, configured in the DI, available in PaaS, able to find and configure a Cluster instance
 * with a driver corresponding with the type required.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
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
    public function require(
        string $type,
        DefaultsBag $defaultsBag,
        Cluster $cluster,
        PromiseInterface $promise
    ): self {
        if (!isset($this->clients[$type])) {
            $promise->fail(new DomainException("No available client for $type", 500));

            return $this;
        }

        $cluster->configureCluster($this->clients[$type], $defaultsBag, $promise);

        return $this;
    }
}
