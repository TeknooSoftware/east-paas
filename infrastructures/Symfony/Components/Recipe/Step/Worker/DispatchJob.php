<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Symfony\Recipe\Step\Worker;

use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\MessageJob;
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
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class DispatchJob implements DispatchJobInterface
{
    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function __invoke(Project $project, Environment $environment, Job $job, string $jobSerialized): self
    {
        $this->bus->dispatch(
            new Envelope(
                message: new MessageJob(
                    projectId: $project->getId(),
                    environment: (string) $environment,
                    jobId: $job->getId(),
                    message: $jobSerialized
                ),
                stamps: [
                    new Parameter('projectId', $project->getId()),
                    new Parameter('envName', (string) $environment),
                    new Parameter('jobId', $job->getId()),
                ]
            )
        );

        return $this;
    }
}
