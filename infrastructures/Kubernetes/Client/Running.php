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
class Running implements StateInterface
{
    use StateTrait;

    private function getMasterUrl(): \Closure
    {
        return function (): string {
            return (string) $this->master;
        };
    }

    private function getCredentials(): \Closure
    {
        return function (): ?ClusterCredentials {
            return $this->credentials;
        };
    }
}
