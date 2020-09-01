<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Step\Worker;

use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Conductor\ConductorInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

class ConfigureConductor
{
    private ConductorInterface $conductor;

    public function __construct(ConductorInterface $conductor)
    {
        $this->conductor = $conductor;
    }

    public function __invoke(
        JobUnitInterface $job,
        JobWorkspaceInterface $workspace,
        ManagerInterface $manager
    ): self {
        $conductor = $this->conductor->configure($job, $workspace);
        $manager->updateWorkPlan([ConductorInterface::class => $conductor]);

        return $this;
    }
}
