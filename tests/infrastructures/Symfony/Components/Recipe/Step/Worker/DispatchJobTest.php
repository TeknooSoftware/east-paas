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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Recipe\Step\Worker;

use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\MessageJob as JobMessage;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Parameter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Paas\Infrastructures\Symfony\Recipe\Step\Worker\DispatchJob;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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
                new JobMessage('foo', 'prod', 'bar', $sJob), [
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
