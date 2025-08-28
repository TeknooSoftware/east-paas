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

namespace Teknoo\Tests\East\Paas\Recipe\Step\Job;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Loader\JobLoader;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Recipe\Step\Job\GetJob;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(GetJob::class)]
class GetJobTest extends TestCase
{
    private (JobLoader&MockObject)|null $jobLoader = null;

    public function getJobLoaderMock(): JobLoader&MockObject
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
        );
    }

    public function testInvoke(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(ClientInterface::class);
        $job = $this->createMock(Job::class);

        $jobId = 'dev';

        $this->getJobLoaderMock()
            ->expects($this->once())
            ->method('load')
            ->with($jobId)
            ->willReturnCallback(function ($criteria, PromiseInterface $promise) use ($job): \Teknoo\East\Paas\Loader\JobLoader&\PHPUnit\Framework\MockObject\MockObject {
                $promise->success($job);

                return $this->getJobLoaderMock();
            });

        $manager->expects($this->once())
            ->method('updateWorkPlan')
            ->with([Job::class => $job]);

        $this->assertInstanceOf(
            GetJob::class,
            $this->buildStep()($jobId, $manager, $client)
        );
    }

    public function testInvokeFailureOnJobLoading(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(ClientInterface::class);

        $jobId = 'dev';
        $exception = new \DomainException();

        $this->getJobLoaderMock()
            ->expects($this->once())
            ->method('load')
            ->with($jobId)
            ->willReturnCallback(function ($criteria, PromiseInterface $promise) use ($exception): \Teknoo\East\Paas\Loader\JobLoader&\PHPUnit\Framework\MockObject\MockObject {
                $promise->fail($exception);

                return $this->getJobLoaderMock();
            });

        $manager->expects($this->never())
            ->method('updateWorkPlan');

        $this->expectException(\DomainException::class);

        $this->assertInstanceOf(
            GetJob::class,
            $this->buildStep()($jobId, $manager, $client)
        );
    }
}
