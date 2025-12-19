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
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Recipe\Step\Job\SaveJob;
use Teknoo\East\Paas\Writer\JobWriter;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(SaveJob::class)]
class SaveJobTest extends TestCase
{
    private (JobWriter&MockObject)|(JobWriter&Stub)|null $jobWriter = null;

    public function getjobWriterMock(bool $stub = false): (JobWriter&Stub)|(JobWriter&MockObject)
    {
        if (!$this->jobWriter instanceof JobWriter) {
            if ($stub) {
                $this->jobWriter = $this->createStub(JobWriter::class);
            } else {
                $this->jobWriter = $this->createMock(JobWriter::class);
            }
        }

        return $this->jobWriter;
    }

    public function buildStep(): SaveJob
    {
        return new SaveJob($this->getjobWriterMock(true));
    }

    public function testInvoke(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(ClientInterface::class);
        $job = $this->createStub(Job::class);

        $this->getjobWriterMock()
            ->expects($this->once())
            ->method('save')
            ->with($job)
            ->willReturnCallback(function ($job, PromiseInterface $promise): \Teknoo\East\Paas\Writer\JobWriter&\PHPUnit\Framework\MockObject\MockObject {
                $promise->success($job);

                return $this->getjobWriterMock();
            });

        $client->expects($this->never())
            ->method('errorInRequest');

        $manager->expects($this->never())
            ->method('finish');

        $this->assertInstanceOf(
            SaveJob::class,
            $this->buildStep()($job, $manager, $client)
        );
    }

    public function testInvokeFailureOnProjectLoading(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createStub(ClientInterface::class);
        $job = $this->createStub(Job::class);

        $exception = new \DomainException();

        $this->getjobWriterMock()
            ->expects($this->once())
            ->method('save')
            ->with($job)
            ->willReturnCallback(function ($job, PromiseInterface $promise) use ($exception): \Teknoo\East\Paas\Writer\JobWriter&\PHPUnit\Framework\MockObject\MockObject {
                $promise->fail($exception);

                return $this->getjobWriterMock();
            });

        $manager->expects($this->once())
            ->method('error');

        $this->assertInstanceOf(
            SaveJob::class,
            $this->buildStep()($job, $manager, $client)
        );
    }
}
