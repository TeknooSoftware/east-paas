<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Step\Worker;

use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

class PrepareWorkspace
{
    private JobWorkspaceInterface $workspace;

    public function __construct(JobWorkspaceInterface $workspace)
    {
        $this->workspace = $workspace;
    }

    public function __invoke(JobUnitInterface $job, ManagerInterface $manager): self
    {
        $workspace = $this->workspace->setJob($job);

        $manager->updateWorkPlan([JobWorkspaceInterface::class => $workspace]);
        return $this;
    }
}
