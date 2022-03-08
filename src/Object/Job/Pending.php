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

namespace Teknoo\East\Paas\Object\Job;

use Closure;
use DateTimeInterface;
use RuntimeException;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Contracts\Object\ImageRegistryInterface;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * State representing a new job, not executed
 *
 * @mixin Job
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Pending implements StateInterface
{
    use StateTrait;

    private function settingProject(): Closure
    {
        return function (Project $project): Job {
            $this->project = $project;

            $this->updateStates();

            return $this;
        };
    }

    private function settingEnvironment(): Closure
    {
        return function (Environment $environment): Job {
            $this->environment = $environment;

            $this->updateStates();

            return $this;
        };
    }

    private function settingSourceRepository(): Closure
    {
        return function (SourceRepositoryInterface $repository): Job {
            $this->sourceRepository = $repository;

            $this->updateStates();

            return $this;
        };
    }

    private function settingImagesRegistry(): Closure
    {
        return function (ImageRegistryInterface $repository): Job {
            $this->imagesRegistry = $repository;

            $this->updateStates();

            return $this;
        };
    }

    private function addingCluster(): Closure
    {
        return function (Cluster $cluster): Job {
            foreach ($this->clusters as $current) {
                if ($current === $cluster) {
                    return $this;
                }
            }

            $this->clusters[] = $cluster;

            $this->updateStates();

            return $this;
        };
    }

    public function isRunnable(): Closure
    {
        return function (PromiseInterface $promise): Job {
            $promise->fail(new RuntimeException('teknoo.east.paas.error.job.not_runnable', 500));

            return $this;
        };
    }

    public function validate(): Closure
    {
        return function (DateTimeInterface $date): Job {
            $this->addToHistory('teknoo.east.paas.error.job.not_validated', $date, true, ['code' => 400]);

            return $this;
        };
    }
}
