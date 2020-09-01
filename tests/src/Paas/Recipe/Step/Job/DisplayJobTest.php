<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\Job;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Recipe\Step\Job\DisplayJob;
use Teknoo\Recipe\ChefInterface;

/**
 * @covers \Teknoo\East\Paas\Recipe\Step\Job\DisplayJob
 * @covers \Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait
 * @covers \Teknoo\East\Paas\Recipe\Traits\ResponseTrait
 */
class DisplayJobTest extends TestCase
{
    public function buildStep(): DisplayJob
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::any())->method('withAddedHeader')->willReturnSelf();
        $response->expects(self::any())->method('withBody')->willReturnSelf();

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory->expects(self::any())->method('createResponse')->willReturn(
            $response
        );

        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $streamFactory->expects(self::any())->method('createStream')->willReturn(
            $this->createMock(StreamInterface::class)
        );

        return new DisplayJob(
            $responseFactory,
            $streamFactory
        );
    }

    public function testInvoke()
    {
        $chef = $this->createMock(ChefInterface::class);
        $job = $this->createMock(Job::class);
        $client = $this->createMock(ClientInterface::class);

        $client->expects(self::once())
            ->method('acceptResponse')
            ->with(self::callback(function ($response) {
                return $response instanceof ResponseInterface;
            }));

        $chef->expects(self::once())
            ->method('finish')
            ->with($job);

        self::assertInstanceOf(
            DisplayJob::class,
            $this->buildStep()($job, $client, $chef, 'fooBar')
        );
    }
}
