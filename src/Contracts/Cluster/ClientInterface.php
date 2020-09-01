<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Contracts\Cluster;

use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;

interface ClientInterface
{
    public function configure(string $url, ?IdentityInterface $identity): ClientInterface;

    public function deploy(CompiledDeployment $compiledDeployment, PromiseInterface $promise): ClientInterface;

    public function expose(CompiledDeployment $compiledDeployment, PromiseInterface $promise): ClientInterface;
}
