<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Contracts\Recipe\Step\Worker;

use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;

interface DispatchJobInterface
{
    public function __invoke(
        Project $project,
        Environment $environment,
        Job $job,
        string $jobSerialized
    ): DispatchJobInterface;
}
