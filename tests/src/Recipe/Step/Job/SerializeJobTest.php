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

namespace Teknoo\Tests\East\Paas\Recipe\Step\Job;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Serializing\SerializerInterface;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Recipe\Step\Job\SerializeJob;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(SerializeJob::class)]
class SerializeJobTest extends TestCase
{
    /**
     * @var SerializerInterface
     */
    private $serializerInterface;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SerializerInterface
     */
    public function getSerializerInterfaceMock(): SerializerInterface
    {
        if (!$this->serializerInterface instanceof SerializerInterface) {
            $this->serializerInterface = $this->createMock(SerializerInterface::class);
        }

        return $this->serializerInterface;
    }

    public function buildStep(): SerializeJob
    {
        return new SerializeJob(
            $this->getSerializerInterfaceMock(),
        );
    }

    public function testInvoke()
    {
        $job = $this->createMock(Job::class);

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
                ) use ($sJob) {
                    $promise->success($sJob);

                    return $this->getSerializerInterfaceMock();
                }
            );

        $chef = $this->createMock(ManagerInterface::class);
        $chef->expects($this->once())
            ->method('updateWorkPlan')
            ->with(['jobSerialized' => $sJob])
            ->willReturnSelf();

        $client = $this->createMock(ClientInterface::class);

        self::assertInstanceOf(
            SerializeJob::class,
            $this->buildStep()($job, $chef, $client)
        );
    }

    public function testInvokeOnError()
    {
        $job = $this->createMock(Job::class);

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
                ) {
                    $promise->fail(new \Exception());

                    return $this->getSerializerInterfaceMock();
                }
            );

        $chef = $this->createMock(ManagerInterface::class);
        $chef->expects($this->never())->method('updateWorkPlan');

        $client = $this->createMock(ClientInterface::class);

        $chef->expects($this->once())
            ->method('error');

        self::assertInstanceOf(
            SerializeJob::class,
            $this->buildStep()($job, $chef, $client)
        );
    }
}
