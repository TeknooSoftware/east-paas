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
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Recipe\Step\Job\SaveJob;
use Teknoo\East\Paas\Writer\JobWriter;
use Teknoo\Recipe\ChefInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Recipe\Step\Job\SaveJob
 */
class SaveJobTest extends TestCase
{
    /**
     * @var JobWriter
     */
    private $jobWriter;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|JobWriter
     */
    public function getjobWriterMock(): JobWriter
    {
        if (!$this->jobWriter instanceof JobWriter) {
            $this->jobWriter = $this->createMock(JobWriter::class);
        }

        return $this->jobWriter;
    }

    public function buildStep(): SaveJob
    {
        return new SaveJob($this->getjobWriterMock());
    }

    public function testInvoke()
    {
        $chef = $this->createMock(ChefInterface::class);
        $client = $this->createMock(ClientInterface::class);
        $job = $this->createMock(Job::class);

        $this->getjobWriterMock()
            ->expects(self::once())
            ->method('save')
            ->with($job)
            ->willReturnCallback(function ($job, PromiseInterface $promise) {
                $promise->success($job);

                return $this->getjobWriterMock();
            });

        $client->expects(self::never())
            ->method('errorInRequest');

        $chef->expects(self::never())
            ->method('finish');

        self::assertInstanceOf(
            SaveJob::class,
            $this->buildStep()($job, $chef, $client)
        );
    }

    public function testInvokeFailureOnProjectLoading()
    {
        $chef = $this->createMock(ChefInterface::class);
        $client = $this->createMock(ClientInterface::class);
        $job = $this->createMock(Job::class);

        $projectId = 'dev';
        $exception = new \DomainException();

        $this->getjobWriterMock()
            ->expects(self::once())
            ->method('save')
            ->with($job)
            ->willReturnCallback(function ($job, PromiseInterface $promise) use ($exception) {
                $promise->fail($exception);

                return $this->getjobWriterMock();
            });

        $client->expects(self::once())
            ->method('errorInRequest')
            ->with($exception);

        $chef->expects(self::once())
            ->method('finish')
            ->with($exception);

        self::assertInstanceOf(
            SaveJob::class,
            $this->buildStep()($job, $chef, $client)
        );
    }
}
