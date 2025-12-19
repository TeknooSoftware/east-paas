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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Recipe\Step\Worker;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(DispatchJob::class)]
class DispatchJobTest extends TestCase
{
    private (MessageBusInterface&MockObject)|(MessageBusInterface&Stub)|null $messageBusInterface = null;

    public function getMessageBusInterfaceMock(bool $stub = false): (MessageBusInterface&Stub)|(MessageBusInterface&MockObject)
    {
        if (!$this->messageBusInterface instanceof MessageBusInterface) {
            if ($stub) {
                $this->messageBusInterface = $this->createStub(MessageBusInterface::class);
            } else {
                $this->messageBusInterface = $this->createMock(MessageBusInterface::class);
            }
        }

        return $this->messageBusInterface;
    }

    public function buildStep(?EncryptionInterface $encryption = null): DispatchJob
    {
        return new DispatchJob(
            bus: $this->getMessageBusInterfaceMock(true),
            encryption: $encryption,
        );
    }

    public function testInvoke(): void
    {
        $project = $this->createStub(Project::class);
        $project->method('getId')->willReturn('foo');
        $job = $this->createStub(Job::class);
        $job->method('getId')->willReturn('bar');
        $env = new Environment('prod');

        $sJob = \json_encode($job, JSON_THROW_ON_ERROR);

        $this->getMessageBusInterfaceMock()
            ->expects($this->once())
            ->method('dispatch')
            ->with($envelope = new Envelope(
                new JobMessage('foo', 'prod', 'bar', $sJob),
                [
                new Parameter('projectId', 'foo'),
                new Parameter('envName', 'prod'),
                new Parameter('jobId', 'bar')
            ]
            ))
            ->willReturn($envelope);

        $this->assertInstanceOf(
            DispatchJob::class,
            $this->buildStep()($project, $env, $job, $sJob)
        );
    }

    public function testInvokeWithEncryption(): void
    {
        $project = $this->createStub(Project::class);
        $project->method('getId')->willReturn('foo');
        $job = $this->createStub(Job::class);
        $job->method('getId')->willReturn('bar');
        $env = new Environment('prod');

        $sJob = \json_encode($job, JSON_THROW_ON_ERROR);

        $this->getMessageBusInterfaceMock()
            ->expects($this->once())
            ->method('dispatch')
            ->with($envelope = new Envelope(
                new JobMessage('foo', 'prod', 'bar', $sJob),
                [
                new Parameter('projectId', 'foo'),
                new Parameter('envName', 'prod'),
                new Parameter('jobId', 'bar')
            ]
            ))
            ->willReturn($envelope);


        $encryption = $this->createStub(EncryptionInterface::class);
        $encryption
            ->method('encrypt')
            ->willReturnCallback(
                function (
                    SensitiveContentInterface $message,
                    PromiseInterface $promise,
                ) use ($encryption): MockObject|Stub {
                    $promise->success($message);

                    return $encryption;
                }
            );

        $this->assertInstanceOf(
            DispatchJob::class,
            $this->buildStep($encryption)($project, $env, $job, $sJob)
        );
    }
}
