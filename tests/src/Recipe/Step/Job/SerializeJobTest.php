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

namespace Teknoo\Tests\East\Paas\Recipe\Step\Job;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Serializing\SerializerInterface;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Recipe\Step\Job\SerializeJob;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Recipe\Step\Job\SerializeJob
 */
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

        $sJob = \json_encode($job);

        $this->getSerializerInterfaceMock()
            ->expects(self::once())
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
        $chef->expects(self::once())
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
            ->expects(self::once())
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
        $chef->expects(self::never())->method('updateWorkPlan');

        $client = $this->createMock(ClientInterface::class);

        $this->expectException(\RuntimeException::class);

        self::assertInstanceOf(
            SerializeJob::class,
            $this->buildStep()($job, $chef, $client)
        );
    }
}
