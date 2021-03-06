<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
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

namespace Teknoo\East\Paas\Infrastructures\Symfony\Recipe\Step\Worker;

use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\DispatchJob as MessageJob;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Parameter;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Worker\DispatchJobInterface;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;

/**
 * Step able to dispatch a new created job, aka a job deployment, to a Symfony Messenger Bus, to be executed
 * on a worker
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class DispatchJob implements DispatchJobInterface
{
    public function __construct(
        private MessageBusInterface $bus,
    ) {
    }

    public function __invoke(Project $project, Environment $environment, Job $job, string $jobSerialized): self
    {
        $this->bus->dispatch(
            new Envelope(
                new MessageJob(
                    $project->getId(),
                    (string) $environment,
                    $job->getId(),
                    $jobSerialized
                ),
                [
                    new Parameter('projectId', $project->getId()),
                    new Parameter('envName', (string) $environment),
                    new Parameter('jobId', $job->getId())
                ]
            )
        );

        return $this;
    }
}
