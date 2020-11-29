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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Command\Steps;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Command\Steps\DisplayHistory;
use Teknoo\East\Website\Service\DatesService;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Symfony\Command\Steps\DisplayHistory
 */
class DisplayHistoryTest extends TestCase
{
    private ?DatesService $dateTimeService = null;

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

    public function buildStep(): DisplayHistory
    {
        return new DisplayHistory($this->getDateTimeServiceMock());
    }

    public function testInvokeBadJob()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(new \stdClass(), 'foo');
    }

    public function testInvokeWithoutOutput()
    {
        $this->getDateTimeServiceMock()
            ->expects(self::any())
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback) {
                $callback(new \DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock();
            });

        $job = $this->createMock(JobUnitInterface::class);

        self::assertInstanceOf(
            DisplayHistory::class,
            ($this->buildStep())($job, 'foo')
        );
    }

    public function testInvoke()
    {
        $this->getDateTimeServiceMock()
            ->expects(self::any())
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback) {
                $callback(new \DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock();
            });

        $job = $this->createMock(JobUnitInterface::class);

        $output = $this->createMock(OutputInterface::class);

        $output->expects(self::once())
            ->method('writeln');

        self::assertInstanceOf(
            DisplayHistory::class,
            ($this->buildStep())($job, 'foo', [], $output)
        );
    }
}
