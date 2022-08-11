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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Object\Project;

use Closure;
use DateTimeInterface;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * State representing an project fully completed, able to be deployed.
 *
 * @mixin Project
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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

    public function configure(): Closure
    {
        return function (
            Job $job,
            DateTimeInterface $date,
            Environment $environment,
            ?string $namespace,
            bool $hierarchicalNamespaces,
        ): Project {
            $job->setProject($this);
            $job->setEnvironment($environment);
            $job->setSourceRepository($this->getSourceRepository());
            $job->setImagesRegistry($this->getImagesRegistry());
            $job->setBaseNamespace($namespace);
            $job->useHierarchicalNamespaces($hierarchicalNamespaces);

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
            $environments = [];
            foreach ($this->clusters as $cluster) {
                $cluster->tellMeYourEnvironment($me);
            }

            return $this;
        };
    }
}
