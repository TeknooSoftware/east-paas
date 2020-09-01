<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Object\Account;

use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * @mixin Account
 */
class Inactive implements StateInterface
{
    use StateTrait;

    public function canIPrepareNewJob(): \Closure
    {
        return function (Project $project, Job $job, \DateTimeInterface $date): Account {
            $project->refuseExecution($job, 'teknoo.paas.error.account.inactive', $date);

            return $this;
        };
    }
}
