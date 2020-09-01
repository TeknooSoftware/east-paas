<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Object\Project;

use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * @mixin Project
 */
class Draft implements StateInterface
{
    use StateTrait;

    public function prepareJob(): \Closure
    {
        return function (Job $job, \DateTimeInterface $date): Project {
            $job->setProject($this);

            $this->refuseExecution($job, 'teknoo.paas.error.project.not_executable', $date);

            return $this;
        };
    }
}
