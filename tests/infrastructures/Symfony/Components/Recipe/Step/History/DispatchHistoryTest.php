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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Recipe\Step\History;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\East\Paas\Contracts\Security\EncryptionInterface;
use Teknoo\East\Paas\Contracts\Security\SensitiveContentInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Recipe\Step\History\DispatchHistory;
use Teknoo\East\Paas\Job\History\SerialGenerator;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(DispatchHistory::class)]
class DispatchHistoryTest extends TestCase
{
    /**
     * @var DatesService
     */
    private $dateTimeService;

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
     * @return \PHPUnit\Framework\MockObject\MockObject|SerialGenerator
     */
    public function getSerialGeneratorMock(): SerialGenerator
    {
        if (!$this->generator instanceof SerialGenerator) {
            $this->generator = $this->createMock(SerialGenerator::class);

            $this->generator
                ->expects($this->any())
                ->method('getNewSerialNumber')
                ->willReturn(0);
        }

        return $this->generator;
    }


    public function buildStep(?EncryptionInterface $encryption = null): DispatchHistory
    {
        return new DispatchHistory(
            dateTimeService: $this->getDateTimeServiceMock(),
            bus: $this->getMessageBusMock(),
            generator: $this->getSerialGeneratorMock(),
            preferRealDate: false,
            encryption: $encryption,
        );
    }

    public function testInvokeBadJob()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(new \stdClass(), 'foo');
    }

    public function testInvoke()
    {
        $this->getDateTimeServiceMock()
            ->expects($this->any())
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback) {
                $callback(new \DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock();
            });

        $this->getMessageBusMock()
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        self::assertInstanceOf(
            DispatchHistory::class,
            ($this->buildStep())('foo', 'bar', 'babar', 'foo')
        );
    }

    public function testInvokeWithEncryption()
    {
        $this->getDateTimeServiceMock()
            ->expects($this->any())
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback) {
                $callback(new \DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock();
            });

        $this->getMessageBusMock()
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $encryption = $this->createMock(EncryptionInterface::class);
        $encryption->expects($this->any())
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
            DispatchHistory::class,
            ($this->buildStep($encryption))('foo', 'bar', 'babar', 'foo')
        );
    }
}
