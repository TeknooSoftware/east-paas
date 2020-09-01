<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony\Recipe\Step\Worker;

use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Job as MessageJob;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Parameter;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Worker\DispatchJobInterface;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;

class DispatchJob implements DispatchJobInterface
{
    private MessageBusInterface $bus;

    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }

    public function __invoke(Project $project, Environment $environment, Job $job, string $jobSerialized): self
    {
        $this->bus->dispatch(
            new Envelope(
                new MessageJob($jobSerialized),
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
