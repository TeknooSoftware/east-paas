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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Recipe\Step\Job;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Teknoo\East\Foundation\Client\ClientInterface as EastClient;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Security\EncryptionInterface;
use Teknoo\East\Paas\Contracts\Security\SensitiveContentInterface as MessagePaaS;
use Teknoo\East\Paas\Contracts\Serializing\NormalizerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Recipe\Step\Job\DispatchResult;
use Teknoo\East\Paas\Job\History\SerialGenerator;
use Teknoo\East\Paas\Object\History;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\Tests\East\Paas\ErrorFactory;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(DispatchResult::class)]
class DispatchResultTest extends TestCase
{
    private (DatesService&MockObject)|null $dateTimeService = null;

    private (NormalizerInterface&MockObject)|null $normalizer = null;

    private (MessageBusInterface&MockObject)|null $bus = null;

    private (SerialGenerator&MockObject)|null $generator = null;

    public function getDateTimeServiceMock(): DatesService&MockObject
    {
        if (!$this->dateTimeService instanceof DatesService) {
            $this->dateTimeService = $this->createMock(DatesService::class);
        }

        return $this->dateTimeService;
    }

    public function getMessageBusMock(): MessageBusInterface&MockObject
    {
        if (!$this->bus instanceof MessageBusInterface) {
            $this->bus = $this->createMock(MessageBusInterface::class);
        }

        return $this->bus;
    }

    public function getNormalizer(): NormalizerInterface&MockObject
    {
        if (!$this->normalizer instanceof NormalizerInterface) {
            $this->normalizer = $this->createMock(NormalizerInterface::class);
        }

        return $this->normalizer;
    }

    public function getSerialGeneratorMock(): SerialGenerator&MockObject
    {
        if (!$this->generator instanceof SerialGenerator) {
            $this->generator = $this->createMock(SerialGenerator::class);

            $this->generator
                ->method('getNewSerialNumber')
                ->willReturn(0);
        }

        return $this->generator;
    }

    public function buildStep(?EncryptionInterface $encryption = null): DispatchResult
    {
        return new DispatchResult(
            dateTimeService: $this->getDateTimeServiceMock(),
            bus: $this->getMessageBusMock(),
            normalizer: $this->getNormalizer(),
            errorFactory: new ErrorFactory(),
            generator: $this->getSerialGeneratorMock(),
            encryption: $encryption,
        );
    }

    public function testInvokeBadManager(): void
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(new \stdClass(), $this->createMock(JobUnitInterface::class), 'foo');
    }

    public function testInvokeBadJob(): void
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())($this->createMock(ManagerInterface::class), new \stdClass(), 'foo');
    }

    public function testInvoke(): void
    {
        $client = $this->createMock(EastClient::class);

        $manager = $this->createMock(ManagerInterface::class);
        $project = 'foo';
        $env = 'bar';

        $this->getDateTimeServiceMock()
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback): DatesService&MockObject {
                $callback(new \DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock();
            });

        $this->getNormalizer()
            ->expects($this->once())
            ->method('normalize')
            ->with($result = ['foo' => 'bar'])
            ->willReturnCallback(
                function (
                    $object,
                    PromiseInterface $promise
                ) use ($result): NormalizerInterface&MockObject {
                    $promise->success($result);

                    return $this->getNormalizer();
                }
            );

        $manager->expects($this->once())
            ->method('updateWorkPlan')
            ->willReturnCallback(function (array $values) use ($manager): MockObject {
                $this->assertInstanceOf(History::class, $values[History::class]);
                $this->assertIsString($values['historySerialized']);

                return $manager;
            });

        $this->getMessageBusMock()
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $this->assertInstanceOf(
            DispatchResult::class,
            ($this->buildStep())($manager, $client, $project, $env, 'babar', $result)
        );
    }

    public function testInvokeWithEncryption(): void
    {
        $client = $this->createMock(EastClient::class);

        $manager = $this->createMock(ManagerInterface::class);
        $project = 'foo';
        $env = 'bar';

        $this->getDateTimeServiceMock()
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback): DatesService&MockObject {
                $callback(new \DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock();
            });

        $this->getNormalizer()
            ->expects($this->once())
            ->method('normalize')
            ->with($result = ['foo' => 'bar'])
            ->willReturnCallback(
                function (
                    $object,
                    PromiseInterface $promise
                ) use ($result): NormalizerInterface&MockObject {
                    $promise->success($result);

                    return $this->getNormalizer();
                }
            );

        $manager->expects($this->once())
            ->method('updateWorkPlan')
            ->willReturnCallback(function (array $values) use ($manager): MockObject {
                $this->assertInstanceOf(History::class, $values[History::class]);
                $this->assertIsString($values['historySerialized']);

                return $manager;
            });

        $this->getMessageBusMock()
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));


        $encryption = $this->createMock(EncryptionInterface::class);
        $encryption
            ->method('encrypt')
            ->willReturnCallback(
                function (
                    MessagePaaS $message,
                    PromiseInterface $promise,
                ) use ($encryption): MockObject {
                    $promise->success($message);

                    return $encryption;
                }
            );

        $this->assertInstanceOf(
            DispatchResult::class,
            ($this->buildStep($encryption))($manager, $client, $project, $env, 'babar', $result)
        );
    }

    public function testInvokeWithException(): void
    {
        $client = $this->createMock(EastClient::class);

        $manager = $this->createMock(ManagerInterface::class);
        $project = 'foo';
        $env = 'bar';

        $this->getDateTimeServiceMock()
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback): DatesService&MockObject {
                $callback(new \DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock();
            });

        $error = new \Exception("fooBar", 500);
        $this->getNormalizer()
            ->expects($this->once())
            ->method('normalize')
            ->with($result = ['fooBar'])
            ->willReturnCallback(
                function (
                    $object,
                    PromiseInterface $promise
                ) use ($result): NormalizerInterface&MockObject {
                    $promise->success($result);

                    return $this->getNormalizer();
                }
            );

        $manager->expects($this->once())
            ->method('updateWorkPlan')
            ->willReturnCallback(function (array $values) use ($manager): MockObject {
                $this->assertInstanceOf(History::class, $values[History::class]);
                $this->assertIsString($values['historySerialized']);

                return $manager;
            });

        $this->getMessageBusMock()
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $this->assertInstanceOf(
            DispatchResult::class,
            ($this->buildStep())($manager, $client, $project, $env, 'babar', null, $error)
        );
    }

    public function testInvokeWithNoResult(): void
    {
        $client = $this->createMock(EastClient::class);

        $manager = $this->createMock(ManagerInterface::class);
        $project = 'foo';
        $env = 'bar';

        $this->getDateTimeServiceMock()
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback): DatesService&MockObject {
                $callback(new \DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock();
            });

        $this->getNormalizer()
            ->expects($this->once())
            ->method('normalize')
            ->with($result = [])
            ->willReturnCallback(
                function (
                    $object,
                    PromiseInterface $promise
                ) use ($result): NormalizerInterface&MockObject {
                    $promise->success($result);

                    return $this->getNormalizer();
                }
            );

        $manager->expects($this->once())
            ->method('updateWorkPlan')
            ->willReturnCallback(function (array $values) use ($manager): MockObject {
                $this->assertInstanceOf(History::class, $values[History::class]);
                $this->assertIsString($values['historySerialized']);

                return $manager;
            });

        $this->getMessageBusMock()
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $this->assertInstanceOf(
            DispatchResult::class,
            ($this->buildStep())($manager, $client, $project, $env, 'babar')
        );
    }

    public function testInvokeError(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->method('withAddedHeader')->willReturnSelf();
        $message->method('withBody')->willReturnSelf();

        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(EastClient::class);

        $project = 'foo';
        $env = 'bar';

        $this->getDateTimeServiceMock()
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback): DatesService&MockObject {
                $callback(new \DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock();
            });

        $this->getNormalizer()
            ->expects($this->once())
            ->method('normalize')
            ->with($result = ['foo' => 'bar'])
            ->willReturnCallback(
                function (
                    $object,
                    PromiseInterface $promise
                ) use ($result): NormalizerInterface&MockObject {
                    $promise->success($result);

                    return $this->getNormalizer();
                }
            );

        $manager->expects($this->once())
            ->method('updateWorkPlan')
            ->willReturnCallback(function (array $values) use ($manager): MockObject {
                $this->assertInstanceOf(History::class, $values[History::class]);
                $this->assertIsString($values['historySerialized']);

                return $manager;
            });


        $this->getMessageBusMock()
            ->expects($this->once())
            ->method('dispatch')
            ->willThrowException(new \Exception('foo'));

        $client->expects($this->once())
            ->method('acceptResponse');

        $manager->expects($this->once())
            ->method('finish')
            ->with(new \Exception('foo'));

        $this->assertInstanceOf(
            DispatchResult::class,
            ($this->buildStep())($manager, $client, $project, $env, 'babarz', $result)
        );
    }
}
