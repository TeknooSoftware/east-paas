<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Object\Project;

use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * @mixin Project
 */
class Executable implements StateInterface
{
    use StateTrait;

    public function prepareJob(): \Closure
    {
        return function (Job $job, \DateTimeInterface $date, Environment $environment): Project {
            $this->account->canIPrepareNewJob($this, $job, $date, $environment);

            return $this;
        };
    }

    public function configure(): \Closure
    {
        return function (Job $job, \DateTimeInterface $date, Environment $environment): Project {
            $job->setProject($this);
            $job->setEnvironment($environment);
            $job->setSourceRepository($this->getSourceRepository());
            $job->setImagesRepository($this->getImagesRepository());

            foreach ($this->clusters as $cluster) {
                $cluster->prepareJobForEnvironment($job, $environment);
            }

            $job->validate($date);

            return $this;
        };
    }
}
