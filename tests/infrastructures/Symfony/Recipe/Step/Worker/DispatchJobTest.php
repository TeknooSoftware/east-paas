<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\SerializingRecipe\Step\Worker;

use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Job as JobMessage;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Parameter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Paas\Infrastructures\Symfony\Recipe\Step\Worker\DispatchJob;

/**
 * @covers \Teknoo\East\Paas\Infrastructures\Symfony\Recipe\Step\Worker\DispatchJob
 */
class DispatchJobTest extends TestCase
{
    /**
     * @var MessageBusInterface
     */
    private $messageBusInterface;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|MessageBusInterface
     */
    public function getMessageBusInterfaceMock(): MessageBusInterface
    {
        if (!$this->messageBusInterface instanceof MessageBusInterface) {
            $this->messageBusInterface = $this->createMock(MessageBusInterface::class);
        }

        return $this->messageBusInterface;
    }

    public function buildStep(): DispatchJob
    {
        return new DispatchJob($this->getMessageBusInterfaceMock());
    }

    public function testInvoke()
    {
        $project = $this->createMock(Project::class);
        $project->expects(self::any())->method('getId')->willReturn('foo');
        $job = $this->createMock(Job::class);
        $job->expects(self::any())->method('getId')->willReturn('bar');
        $env = new Environment('prod');

        $sJob = \json_encode($job);

        $this->getMessageBusInterfaceMock()
            ->expects(self::once())
            ->method('dispatch')
            ->with($envelope = new Envelope(
                new JobMessage($sJob), [
                new Parameter('projectId', 'foo'),
                new Parameter('envName', 'prod'),
                new Parameter('jobId', 'bar')
            ]))
            ->willReturn($envelope);

        self::assertInstanceOf(
            DispatchJob::class,
            $this->buildStep()($project, $env, $job, $sJob)
        );
    }
}
