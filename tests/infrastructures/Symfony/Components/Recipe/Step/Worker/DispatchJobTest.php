<?php

declare(strict_types=1);

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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Recipe\Step\Worker;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Teknoo\East\Paas\Contracts\Security\EncryptionInterface;
use Teknoo\East\Paas\Contracts\Security\SensitiveContentInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\MessageJob as JobMessage;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Parameter;
use Teknoo\East\Paas\Infrastructures\Symfony\Recipe\Step\Worker\DispatchJob;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
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

    public function buildStep(?EncryptionInterface $encryption = null): DispatchJob
    {
        return new DispatchJob(
            bus: $this->getMessageBusInterfaceMock(),
            encryption: $encryption,
        );
    }

    public function testInvoke()
    {
        $project = $this->createMock(Project::class);
        $project->expects(self::any())->method('getId')->willReturn('foo');
        $job = $this->createMock(Job::class);
        $job->expects(self::any())->method('getId')->willReturn('bar');
        $env = new Environment('prod');

        $sJob = \json_encode($job, JSON_THROW_ON_ERROR);

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

    public function testInvokeWithEncryption()
    {
        $project = $this->createMock(Project::class);
        $project->expects(self::any())->method('getId')->willReturn('foo');
        $job = $this->createMock(Job::class);
        $job->expects(self::any())->method('getId')->willReturn('bar');
        $env = new Environment('prod');

        $sJob = \json_encode($job, JSON_THROW_ON_ERROR);

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


        $encryption = $this->createMock(EncryptionInterface::class);
        $encryption->expects(self::any())
            ->method('encrypt')
            ->willReturnCallback(
                function (
                    SensitiveContentInterface $message,
                    PromiseInterface $promise,
                ) use ($encryption) {
                    $promise->success($message);

                    return $encryption;
                }
            );

        self::assertInstanceOf(
            DispatchJob::class,
            $this->buildStep($encryption)($project, $env, $job, $sJob)
        );
    }
}
