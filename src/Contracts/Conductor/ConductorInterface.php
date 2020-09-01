<?php

declare(strict_types=1);

/*
 * @copright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Contracts\Conductor;

use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;

interface ConductorInterface
{
    public function configure(
        JobUnitInterface $job,
        JobWorkspaceInterface $workspace
    ): ConductorInterface;

    public function prepare(
        string $configuration,
        PromiseInterface $promise
    ): ConductorInterface;

    public function compileDeployment(PromiseInterface $promise): ConductorInterface;
}
