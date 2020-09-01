<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts;

use Maclof\Kubernetes\Client;
use Teknoo\East\Paas\Object\ClusterCredentials;

interface ClientFactoryInterface
{
    public function __invoke(string $master, ?ClusterCredentials $credentials): Client;
}
