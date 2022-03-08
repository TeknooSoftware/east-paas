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

namespace Teknoo\Tests\East\Paas\Recipe\Step\Job;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Website\Service\DatesService;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Paas\Recipe\Step\Job\PrepareJob;
use Teknoo\Tests\East\Paas\ErrorFactory;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Recipe\Step\Job\PrepareJob
 */
class PrepareJobTest extends TestCase
{
    /**
     * @var DatesService
     */
    private $dateTimeService;

    /**
     * @return DatesService|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getDateTimeServiceMock(): DatesService
    {
        if (!$this->dateTimeService instanceof DatesService) {
            $this->dateTimeService = $this->createMock(DatesService::class);
        }

        return $this->dateTimeService;
    }

    public function buildStep(): PrepareJob
    {
        return new PrepareJob(
            $this->getDateTimeServiceMock(),
            new ErrorFactory(),
        );
    }

    public function testInvoke()
    {
        $project = $this->createMock(Project::class);
        $env = $this->createMock(Environment::class);
        $job = $this->createMock(Job::class);

        $this->getDateTimeServiceMock()
            ->expects(self::any())
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback) {
                $callback(new \DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock();
            });

        $project->expects(self::once())
            ->method('__call')
            ->with('prepareJob', [$job, new \DateTime('2018-08-01'), $env]);


        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects(self::never())->method('stop');
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::never())->method('acceptResponse');

        self::assertInstanceOf(
            PrepareJob::class,
            $this->buildStep()($project, $env, $job, $manager, $client)
        );
    }

    public function testInvokeErrorJobNotRunnable()
    {
        $project = $this->createMock(Project::class);
        $env = $this->createMock(Environment::class);
        $job = new Job();

        $this->getDateTimeServiceMock()
            ->expects(self::any())
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback) {
                $callback(new \DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock();
            });

        $project->expects(self::once())
            ->method('__call')
            ->with('prepareJob', [$job, new \DateTime('2018-08-01'), $env]);


        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects(self::once())->method('finish');
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('acceptResponse');

        self::assertInstanceOf(
            PrepareJob::class,
            $this->buildStep()($project, $env, $job, $manager, $client)
        );
    }
}
