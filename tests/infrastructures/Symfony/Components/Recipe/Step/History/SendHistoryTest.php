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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Recipe\Step\History;

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Recipe\Step\History\SendHistory;
use Teknoo\East\Paas\Job\History\SerialGenerator;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Common\Service\DatesService;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Symfony\Recipe\Step\History\SendHistory
 */
class SendHistoryTest extends TestCase
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
                ->expects(self::any())
                ->method('getNewSerialNumber')
                ->willReturn(0);
        }

        return $this->generator;
    }


    public function buildStep(): SendHistory
    {
        return new SendHistory(
            $this->getDateTimeServiceMock(),
            $this->getMessageBusMock(),
            $this->getSerialGeneratorMock(),
        );
    }

    public function testInvokeBadJob()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(new \stdClass(), 'foo');
    }

    public function testInvoke()
    {
        $project = $this->createMock(Project::class);
        $env = $this->createMock(Environment::class);
        $job = $this->createMock(JobUnitInterface::class);

        $this->getDateTimeServiceMock()
            ->expects(self::any())
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback) {
                $callback(new \DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock();
            });

        $this->getMessageBusMock()
            ->expects(self::once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        self::assertInstanceOf(
            SendHistory::class,
            ($this->buildStep())('foo', 'bar', 'babar', 'foo')
        );
    }
}
