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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Recipe\Step\History;

use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\East\Paas\Contracts\Security\EncryptionInterface;
use Teknoo\East\Paas\Contracts\Security\SensitiveContentInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Recipe\Step\History\DispatchHistory;
use Teknoo\East\Paas\Job\History\SerialGenerator;
use Teknoo\Recipe\Promise\PromiseInterface;
use TypeError;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(DispatchHistory::class)]
class DispatchHistoryTest extends TestCase
{
    private (DatesService&MockObject)|(DatesService&Stub)|null $dateTimeService = null;

    private (MessageBusInterface&MockObject)|(MessageBusInterface&Stub)|null $bus = null;

    private (SerialGenerator&MockObject)|(SerialGenerator&Stub)|null $generator = null;

    public function getDateTimeServiceMock(bool $stub = false): (DatesService&Stub)|(DatesService&MockObject)
    {
        if (!$this->dateTimeService instanceof DatesService) {
            if ($stub) {
                $this->dateTimeService = $this->createStub(DatesService::class);
            } else {
                $this->dateTimeService = $this->createMock(DatesService::class);
            }
        }

        return $this->dateTimeService;
    }

    public function getMessageBusMock(bool $stub = false): (MessageBusInterface&Stub)|(MessageBusInterface&MockObject)
    {
        if (!$this->bus instanceof MessageBusInterface) {
            if ($stub) {
                $this->bus = $this->createStub(MessageBusInterface::class);
            } else {
                $this->bus = $this->createMock(MessageBusInterface::class);
            }
        }

        return $this->bus;
    }

    public function getSerialGeneratorMock(bool $stub = false): (SerialGenerator&MockObject)|(SerialGenerator&Stub)
    {
        if (!$this->generator instanceof SerialGenerator) {
            if ($stub) {
                $this->generator = $this->createStub(SerialGenerator::class);
            } else {
                $this->generator = $this->createMock(SerialGenerator::class);
            }

            $this->generator
                ->method('getNewSerialNumber')
                ->willReturn(0);
        }

        return $this->generator;
    }

    public function buildStep(?EncryptionInterface $encryption = null): DispatchHistory
    {
        return new DispatchHistory(
            dateTimeService: $this->getDateTimeServiceMock(true),
            bus: $this->getMessageBusMock(true),
            generator: $this->getSerialGeneratorMock(true),
            preferRealDate: false,
            encryption: $encryption,
        );
    }

    public function testInvokeBadJob(): void
    {
        $this->expectException(TypeError::class);
        ($this->buildStep())(new stdClass(), 'foo');
    }

    public function testInvoke(): void
    {
        $this->getDateTimeServiceMock(true)
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback): (DatesService&MockObject)|(DatesService&Stub) {
                $callback(new DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock(true);
            });

        $this->getMessageBusMock()
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(new Envelope(new stdClass()));

        $this->assertInstanceOf(
            DispatchHistory::class,
            ($this->buildStep())('foo', 'bar', 'babar', 'foo')
        );
    }

    public function testInvokeWithEncryption(): void
    {
        $this->getDateTimeServiceMock(true)
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback): (DatesService&MockObject)|(DatesService&Stub) {
                $callback(new DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock(true);
            });

        $this->getMessageBusMock()
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(new Envelope(new stdClass()));

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
            DispatchHistory::class,
            ($this->buildStep($encryption))('foo', 'bar', 'babar', 'foo')
        );
    }
}
