<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Object\Project;

use Closure;
use DateTimeInterface;
use Teknoo\East\Paas\Object\AccountQuota;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * State representing an project fully completed, able to be deployed.
 *
 * @mixin Project
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Executable implements StateInterface
{
    use StateTrait;

    public function prepareJob(): Closure
    {
        return function (Job $job, DateTimeInterface $date, Environment $environment): Project {
            $this->account->canIPrepareNewJob($this, $job, $date, $environment);

            return $this;
        };
    }

    /**
     * @param AccountQuota[] $quotas
     */
    public function configure(): Closure
    {
        return function (
            Job $job,
            DateTimeInterface $date,
            Environment $environment,
            ?iterable $quotas = [],
        ): Project {
            $job->setProject($this);
            $job->setEnvironment($environment);
            $job->setSourceRepository($this->sourceRepository);
            $job->setImagesRegistry($this->imagesRegistry);
            $job->setPrefix($this->getPrefix());
            if (!empty($quotas)) {
                $job->setQuotas($quotas);
            }

            foreach ($this->clusters as $cluster) {
                $cluster->prepareJobForEnvironment($job, $environment);
            }

            $job->validate($date);

            return $this;
        };
    }

    public function listMeYourEnvironments(): Closure
    {
        return function (callable $me): Project {
            foreach ($this->clusters as $cluster) {
                $cluster->tellMeYourEnvironment($me);
            }

            return $this;
        };
    }

    public function isRunnable(): Closure
    {
        return function (): bool {
            return true;
        };
    }
}
