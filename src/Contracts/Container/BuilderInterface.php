<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Contracts\Container;

use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;

interface BuilderInterface
{
    public function configure(string $url, ?IdentityInterface $auth): BuilderInterface;

    public function buildImages(
        CompiledDeployment $compiledDeployment,
        PromiseInterface $promise
    ): BuilderInterface;

    public function buildVolumes(
        CompiledDeployment $compiledDeployment,
        string $workingPath,
        PromiseInterface $promise
    ): BuilderInterface;
}
