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
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Loader\JobLoader;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Recipe\Step\Job\GetJob;
use Teknoo\East\Foundation\Promise\PromiseInterface;

/**
 * @covers \Teknoo\East\Paas\Recipe\Step\Job\GetJob
 * @covers \Teknoo\East\Paas\Recipe\Traits\ErrorTrait
 * @covers \Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait
 */
class GetJobTest extends TestCase
{
    /**
     * @var JobLoader
     */
    private $jobLoader;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|JobLoader
     */
    public function getJobLoaderMock(): JobLoader
    {
        if (!$this->jobLoader instanceof JobLoader) {
            $this->jobLoader = $this->createMock(JobLoader::class);
        }

        return $this->jobLoader;
    }

    public function buildStep(): GetJob
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

        return new GetJob(
            $this->getJobLoaderMock(),
            $responseFactory,
            $streamFactory
        );
    }

    public function testInvoke()
    {
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(ClientInterface::class);
        $job = $this->createMock(Job::class);

        $jobId = 'dev';

        $this->getJobLoaderMock()
            ->expects(self::once())
            ->method('load')
            ->with($jobId)
            ->willReturnCallback(function ($criteria, PromiseInterface $promise) use ($job) {
                $promise->success($job);

                return $this->getJobLoaderMock();
            });

        $manager->expects(self::once())
            ->method('updateWorkPlan')
            ->with(['job' => $job]);

        self::assertInstanceOf(
            GetJob::class,
            $this->buildStep()($jobId, $manager, $client)
        );
    }

    public function testInvokeFailureOnJobLoading()
    {
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(ClientInterface::class);

        $jobId = 'dev';
        $exception = new \DomainException();

        $this->getJobLoaderMock()
            ->expects(self::once())
            ->method('load')
            ->with($jobId)
            ->willReturnCallback(function ($criteria, PromiseInterface $promise) use ($exception) {
                $promise->fail($exception);

                return $this->getJobLoaderMock();
            });

        $client->expects(self::once())
            ->method('acceptResponse');

        $manager->expects(self::once())
            ->method('finish')
            ->with($exception);

        $manager->expects(self::never())
            ->method('updateWorkPlan');

        self::assertInstanceOf(
            GetJob::class,
            $this->buildStep()($jobId, $manager, $client)
        );
    }
}
