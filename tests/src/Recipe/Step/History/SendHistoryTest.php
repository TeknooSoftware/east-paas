<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\History;

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Teknoo\East\Website\Service\DatesService;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Recipe\Step\History\SendHistory;
use Teknoo\East\Foundation\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Recipe\Step\History\SendHistory
 * @covers \Teknoo\East\Paas\Recipe\Traits\RequestTrait
 */
class SendHistoryTest extends TestCase
{
    /**
     * @var DatesService
     */
    private $dateTimeService;

    private ?UriFactoryInterface $uriFactory = null;

    private ?RequestFactoryInterface $requestFactory = null;

    private ?StreamFactoryInterface $streamFactory = null;

    private ?ClientInterface $client = null;

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
     * @return \PHPUnit\Framework\MockObject\MockObject|ClientInterface
     */
    public function getClientMock(): ClientInterface
    {
        if (!$this->client instanceof ClientInterface) {
            $this->client = $this->createMock(ClientInterface::class);
        }

        return $this->client;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|UriFactoryInterface
     */
    public function getUriFactoryMock(): UriFactoryInterface
    {
        if (!$this->uriFactory instanceof UriFactoryInterface) {
            $this->uriFactory = $this->createMock(UriFactoryInterface::class);
        }

        return $this->uriFactory;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|RequestFactoryInterface
     */
    public function getRequestFactoryMock(): RequestFactoryInterface
    {
        if (!$this->requestFactory instanceof RequestFactoryInterface) {
            $this->requestFactory = $this->createMock(RequestFactoryInterface::class);
        }

        return $this->requestFactory;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|StreamFactoryInterface
     */
    public function getStreamFactoryMock(): StreamFactoryInterface
    {
        if (!$this->streamFactory instanceof StreamFactoryInterface) {
            $this->streamFactory = $this->createMock(StreamFactoryInterface::class);
        }

        return $this->streamFactory;
    }

    public function buildStep(): SendHistory
    {
        return new SendHistory(
            $this->getDateTimeServiceMock(),
            'https://foo.bar',
            $this->getUriFactoryMock(),
            $this->getRequestFactoryMock(),
            $this->getStreamFactoryMock(),
            $this->getClientMock()
        );
    }

    public function testInvokeBadJob()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(new \stdClass(), 'foo');
    }

    public function testInvoke()
    {
        $request = $this->createMock(RequestInterface::class);
        $request->expects(self::any())->method('withAddedHeader')->willReturnSelf();
        $request->expects(self::any())->method('withBody')->willReturnSelf();

        $this->getRequestFactoryMock()->expects(self::any())->method('createRequest')->willReturn(
            $request
        );

        $this->getStreamFactoryMock()->expects(self::any())->method('createStream')->willReturn(
            $this->createMock(StreamInterface::class)
        );

        $job = $this->createMock(JobUnitInterface::class);
        $job->expects(self::once())
            ->method('prepareUrl')
            ->willReturnCallback(function ($url, PromiseInterface $promise) use ($job) {
                $promise->success('https://foo.bar');

                return $job;
            });

        $this->getDateTimeServiceMock()
            ->expects(self::any())
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback) {
                $callback(new \DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock();
            });

        $this->getUriFactoryMock()
            ->expects(self::once())
            ->method('createUri')
            ->with('https://foo.bar')
            ->willReturn($this->createMock(UriInterface::class));

        $this->getClientMock()
            ->expects(self::once())
            ->method('sendRequest');

        self::assertInstanceOf(
            SendHistory::class,
            ($this->buildStep())($job, 'foo')
        );
    }
}
