<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Client;

use Teknoo\East\Paas\Infrastructures\Kubernetes\Client;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * @mixin Client
 */
class Generator implements StateInterface
{
    use StateTrait;

    private function getMasterUrl(): \Closure
    {
        return function (): string {
            throw new \RuntimeException('Client is in generator state');
        };
    }

    private function getCredentials(): \Closure
    {
        return function (): ?ClusterCredentials {
            throw new \RuntimeException('Client is in generator state');
        };
    }
}
