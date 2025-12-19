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
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Paas\Recipe\Step\Job\PrepareJob;
use Teknoo\Tests\East\Paas\ErrorFactory;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(PrepareJob::class)]
class PrepareJobTest extends TestCase
{
    private (DatesService&MockObject)|(DatesService&Stub)|null $dateTimeService = null;

    private function getDateTimeServiceMock(bool $stub = false): (DatesService&Stub)|(DatesService&MockObject)
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

    public function buildStep(): PrepareJob
    {
        return new PrepareJob(
            $this->getDateTimeServiceMock(true),
            new ErrorFactory(),
        );
    }

    public function testInvoke(): void
    {
        $project = $this->createMock(Project::class);
        $env = $this->createStub(Environment::class);
        $job = $this->createStub(Job::class);

        $this->getDateTimeServiceMock(true)
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback): (DatesService&MockObject)|(DatesService&Stub) {
                $callback(new \DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock();
            });

        $project->expects($this->once())
            ->method('__call')
            ->with('prepareJob', [$job, new \DateTime('2018-08-01'), $env]);


        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->never())->method('stop');
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->never())->method('acceptResponse');

        $this->assertInstanceOf(
            PrepareJob::class,
            $this->buildStep()($project, $env, $job, $manager, $client)
        );
    }

    public function testInvokeErrorJobNotRunnable(): void
    {
        $project = $this->createMock(Project::class);
        $env = $this->createStub(Environment::class);
        $job = new Job();

        $this->getDateTimeServiceMock(true)
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback): (DatesService&MockObject)|(DatesService&Stub) {
                $callback(new \DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock();
            });

        $project->expects($this->once())
            ->method('__call')
            ->with('prepareJob', [$job, new \DateTime('2018-08-01'), $env]);


        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->once())->method('finish');
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())->method('acceptResponse');

        $this->assertInstanceOf(
            PrepareJob::class,
            $this->buildStep()($project, $env, $job, $manager, $client)
        );
    }
}
