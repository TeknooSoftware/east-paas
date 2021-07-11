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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
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
use Teknoo\East\Paas\Loader\JobLoader;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Recipe\Step\Job\GetJob;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\Tests\East\Paas\ErrorFactory;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Recipe\Step\Job\GetJob
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
        return new GetJob(
            $this->getJobLoaderMock(),
            new ErrorFactory(),
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
            ->with([Job::class => $job]);

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
