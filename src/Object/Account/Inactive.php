<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Object\Account;

use Closure;
use DateTimeInterface;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * State representing an account non fully completed, unable to create new project.
 *
 * @mixin Account
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Inactive implements StateInterface
{
    use StateTrait;

    public function canIPrepareNewJob(): Closure
    {
        return function (Project $project, Job $job, DateTimeInterface $date): Account {
            $project->refuseExecution($job, 'teknoo.east.paas.error.account.inactive', $date);

            return $this;
        };
    }
}
