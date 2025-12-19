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

namespace Teknoo\Tests\East\Paas\Recipe\Step\Job;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Serializing\SerializerInterface;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Recipe\Step\Job\SerializeJob;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(SerializeJob::class)]
class SerializeJobTest extends TestCase
{
    private (SerializerInterface&MockObject)|(SerializerInterface&Stub)|null $serializerInterface = null;

    public function getSerializerInterfaceMock(bool $stub = false): (SerializerInterface&Stub)|(SerializerInterface&MockObject)
    {
        if (!$this->serializerInterface instanceof SerializerInterface) {
            if ($stub) {
                $this->serializerInterface = $this->createStub(SerializerInterface::class);
            } else {
                $this->serializerInterface = $this->createMock(SerializerInterface::class);
            }
        }

        return $this->serializerInterface;
    }

    public function buildStep(): SerializeJob
    {
        return new SerializeJob(
            $this->getSerializerInterfaceMock(true),
        );
    }

    public function testInvoke(): void
    {
        $job = $this->createStub(Job::class);

        $sJob = \json_encode($job, JSON_THROW_ON_ERROR);

        $this->getSerializerInterfaceMock()
            ->expects($this->once())
            ->method('serialize')
            ->with($job, 'json')
            ->willReturnCallback(
                function (
                    $data,
                    string $format,
                    PromiseInterface $promise,
                    array $context = []
                ) use ($sJob): (SerializerInterface&MockObject)|(SerializerInterface&Stub) {
                    $promise->success($sJob);

                    return $this->getSerializerInterfaceMock();
                }
            );

        $chef = $this->createMock(ManagerInterface::class);
        $chef->expects($this->once())
            ->method('updateWorkPlan')
            ->with(['jobSerialized' => $sJob])
            ->willReturnSelf();

        $client = $this->createStub(ClientInterface::class);

        $this->assertInstanceOf(
            SerializeJob::class,
            $this->buildStep()($job, $chef, $client)
        );
    }

    public function testInvokeOnError(): void
    {
        $job = $this->createStub(Job::class);

        $this->getSerializerInterfaceMock()
            ->expects($this->once())
            ->method('serialize')
            ->with($job, 'json')
            ->willReturnCallback(
                function (
                    $data,
                    string $format,
                    PromiseInterface $promise,
                    array $context = []
                ): (SerializerInterface&MockObject)|(SerializerInterface&Stub) {
                    $promise->fail(new \Exception());

                    return $this->getSerializerInterfaceMock();
                }
            );

        $chef = $this->createMock(ManagerInterface::class);
        $chef->expects($this->never())->method('updateWorkPlan');

        $client = $this->createStub(ClientInterface::class);

        $chef->expects($this->once())
            ->method('error');

        $this->assertInstanceOf(
            SerializeJob::class,
            $this->buildStep()($job, $chef, $client)
        );
    }
}
