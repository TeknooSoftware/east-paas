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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Recipe\Step\Job;

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
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Infrastructures\Symfony\Recipe\Step\Job\DispatchResult
 */
class DispatchResultTest extends TestCase
{
    /**
     * @var DatesService
     */
    private $dateTimeService;

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    private ?MessageBusInterface $bus = null;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|SerialGenerator
     */
    private $generator;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DatesService
     */
    public function getDateTimeServiceMock(): DatesService
    {
        if (!$this->dateTimeService instanceof DatesService) {
            $this->dateTimeService = $this->createMock(DatesService::class);
        }

        return $this->dateTimeService;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|MessageBusInterface
     */
    public function getMessageBusMock(): MessageBusInterface
    {
        if (!$this->bus instanceof MessageBusInterface) {
            $this->bus = $this->createMock(MessageBusInterface::class);
        }

        return $this->bus;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|NormalizerInterface
     */
    public function getNormalizer(): NormalizerInterface
    {
        if (!$this->normalizer instanceof NormalizerInterface) {
            $this->normalizer = $this->createMock(NormalizerInterface::class);
        }

        return $this->normalizer;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SerialGenerator
     */
    public function getSerialGeneratorMock(): SerialGenerator
    {
        if (!$this->generator instanceof SerialGenerator) {
            $this->generator = $this->createMock(SerialGenerator::class);

            $this->generator
                ->expects(self::any())
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

    public function testInvokeBadManager()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(new \stdClass(), $this->createMock(JobUnitInterface::class), 'foo');
    }

    public function testInvokeBadJob()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())($this->createMock(ManagerInterface::class), new \stdClass(), 'foo');
    }

    public function testInvoke()
    {
        $client = $this->createMock(EastClient::class);

        $manager = $this->createMock(ManagerInterface::class);
        $project = 'foo';
        $env = 'bar';

        $this->getDateTimeServiceMock()
            ->expects(self::any())
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback) {
                $callback(new \DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock();
            });

        $this->getNormalizer()
            ->expects(self::once())
            ->method('normalize')
            ->with($result = ['foo' => 'bar'])
            ->willReturnCallback(
                function (
                    $object,
                    PromiseInterface $promise
                ) use ($result) {
                    $promise->success($result);

                    return $this->getNormalizer();
                }
            );

        $manager->expects(self::once())
            ->method('updateWorkPlan')
            ->willReturnCallback(function ($values) use ($manager) {
               self::assertInstanceOf(History::class, $values[History::class]);
               self::assertIsString($values['historySerialized']);

               return $manager;
            });

        $this->getMessageBusMock()
            ->expects(self::once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        self::assertInstanceOf(
            DispatchResult::class,
            ($this->buildStep())($manager, $client, $project, $env, 'babar', $result)
        );
    }

    public function testInvokeWithEncryption()
    {
        $client = $this->createMock(EastClient::class);

        $manager = $this->createMock(ManagerInterface::class);
        $project = 'foo';
        $env = 'bar';

        $this->getDateTimeServiceMock()
            ->expects(self::any())
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback) {
                $callback(new \DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock();
            });

        $this->getNormalizer()
            ->expects(self::once())
            ->method('normalize')
            ->with($result = ['foo' => 'bar'])
            ->willReturnCallback(
                function (
                    $object,
                    PromiseInterface $promise
                ) use ($result) {
                    $promise->success($result);

                    return $this->getNormalizer();
                }
            );

        $manager->expects(self::once())
            ->method('updateWorkPlan')
            ->willReturnCallback(function ($values) use ($manager) {
               self::assertInstanceOf(History::class, $values[History::class]);
               self::assertIsString($values['historySerialized']);

               return $manager;
            });

        $this->getMessageBusMock()
            ->expects(self::once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));


        $encryption = $this->createMock(EncryptionInterface::class);
        $encryption->expects(self::any())
            ->method('encrypt')
            ->willReturnCallback(
                function (
                    MessagePaaS $message,
                    PromiseInterface $promise,
                ) use ($encryption) {
                    $promise->success($message);

                    return $encryption;
                }
            );

        self::assertInstanceOf(
            DispatchResult::class,
            ($this->buildStep($encryption))($manager, $client, $project, $env, 'babar', $result)
        );
    }

    public function testInvokeWithException()
    {
        $client = $this->createMock(EastClient::class);

        $manager = $this->createMock(ManagerInterface::class);
        $project = 'foo';
        $env = 'bar';

        $this->getDateTimeServiceMock()
            ->expects(self::any())
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback) {
                $callback(new \DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock();
            });

        $error = new \Exception("fooBar", 500);
        $this->getNormalizer()
            ->expects(self::once())
            ->method('normalize')
            ->with($result = ['fooBar'])
            ->willReturnCallback(
                function (
                    $object,
                    PromiseInterface $promise
                ) use ($result) {
                    $promise->success($result);

                    return $this->getNormalizer();
                }
            );

        $manager->expects(self::once())
            ->method('updateWorkPlan')
            ->willReturnCallback(function ($values) use ($manager) {
               self::assertInstanceOf(History::class, $values[History::class]);
               self::assertIsString($values['historySerialized']);

               return $manager;
            });

        $this->getMessageBusMock()
            ->expects(self::once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        self::assertInstanceOf(
            DispatchResult::class,
            ($this->buildStep())($manager, $client, $project, $env, 'babar', null, $error)
        );
    }

    public function testInvokeWithNoResult()
    {
        $client = $this->createMock(EastClient::class);

        $manager = $this->createMock(ManagerInterface::class);
        $project = 'foo';
        $env = 'bar';

        $this->getDateTimeServiceMock()
            ->expects(self::any())
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback) {
                $callback(new \DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock();
            });

        $this->getNormalizer()
            ->expects(self::once())
            ->method('normalize')
            ->with($result = [])
            ->willReturnCallback(
                function (
                    $object,
                    PromiseInterface $promise
                ) use ($result) {
                    $promise->success($result);

                    return $this->getNormalizer();
                }
            );

        $manager->expects(self::once())
            ->method('updateWorkPlan')
            ->willReturnCallback(function ($values) use ($manager) {
               self::assertInstanceOf(History::class, $values[History::class]);
               self::assertIsString($values['historySerialized']);

               return $manager;
            });

        $this->getMessageBusMock()
            ->expects(self::once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        self::assertInstanceOf(
            DispatchResult::class,
            ($this->buildStep())($manager, $client, $project, $env, 'babar')
        );
    }

    public function testInvokeError()
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())->method('withAddedHeader')->willReturnSelf();
        $message->expects(self::any())->method('withBody')->willReturnSelf();

        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(EastClient::class);

        $project = 'foo';
        $env = 'bar';

        $this->getDateTimeServiceMock()
            ->expects(self::any())
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback) {
                $callback(new \DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock();
            });

        $this->getNormalizer()
            ->expects(self::once())
            ->method('normalize')
            ->with($result = ['foo' => 'bar'])
            ->willReturnCallback(
                function (
                    $object,
                    PromiseInterface $promise
                ) use ($result) {
                    $promise->success($result);

                    return $this->getNormalizer();
                }
            );

        $manager->expects(self::once())
            ->method('updateWorkPlan')
            ->willReturnCallback(function ($values) use ($manager) {
                self::assertInstanceOf(History::class, $values[History::class]);
                self::assertIsString($values['historySerialized']);

                return $manager;
            });


        $this->getMessageBusMock()
            ->expects(self::once())
            ->method('dispatch')
            ->willThrowException(new \Exception('foo'));

        $client->expects(self::once())
            ->method('acceptResponse');

        $manager->expects(self::once())
            ->method('finish')
            ->with(new \Exception('foo'));

        self::assertInstanceOf(
            DispatchResult::class,
            ($this->buildStep())($manager, $client, $project, $env, 'babarz', $result)
        );
    }
}
