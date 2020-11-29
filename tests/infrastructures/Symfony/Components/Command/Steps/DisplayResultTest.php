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
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Foundation\Http\ClientInterface as EastClient;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Serializing\NormalizerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Command\Steps\DisplayResult;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Recipe\Step\Misc\PushResultOverHTTP;
use Teknoo\East\Website\Service\DatesService;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Symfony\Command\Steps\DisplayResult
 */
class DisplayResultTest extends TestCase
{
    private ?DatesService $dateTimeService = null;

    private ?NormalizerInterface $normalizer = null;

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
     * @return \PHPUnit\Framework\MockObject\MockObject|NormalizerInterface
     */
    public function getNormalizer(): NormalizerInterface
    {
        if (!$this->normalizer instanceof NormalizerInterface) {
            $this->normalizer = $this->createMock(NormalizerInterface::class);
        }

        return $this->normalizer;
    }

    public function buildStep(): DisplayResult
    {
        return new DisplayResult($this->getDateTimeServiceMock(), $this->getNormalizer());
    }

    public function testInvokeBadManager()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(new \stdClass(), $this->createMock(JobUnitInterface::class), 'foo');
    }

    public function testInvokeBadJob()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())($this->createMock(ManagerInterface::class), new \stdClass(), 'foo');
    }

    public function testInvoke()
    {
        $client = $this->createMock(EastClient::class);

        $manager = $this->createMock(ManagerInterface::class);
        $job = $this->createMock(JobUnitInterface::class);

        $this->getDateTimeServiceMock()
            ->expects(self::any())
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback) {
                $callback(new \DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock();
            });

        $this->getNormalizer()
            ->expects(self::once())
            ->method('normalize')
            ->with($result = ['foo' => 'bar'])
            ->willReturnCallback(
                function (
                    $object,
                    PromiseInterface $promise
                ) use ($result) {
                    $promise->success($result);

                    return $this->getNormalizer();
                }
            );

        $manager->expects(self::once())
            ->method('updateWorkPlan')
            ->willReturnCallback(function ($values) use ($manager) {
                self::assertInstanceOf(History::class, $values[History::class]);
                self::assertIsString($values['historySerialized']);

                return $manager;
            });

        $output = $this->createMock(OutputInterface::class);

        $output->expects(self::once())
            ->method('writeln');

        self::assertInstanceOf(
            DisplayResult::class,
            ($this->buildStep())($manager, $client, $job, $result, null, $output)
        );
    }

    public function testInvokeWithoutOutput()
    {
        $client = $this->createMock(EastClient::class);

        $manager = $this->createMock(ManagerInterface::class);
        $job = $this->createMock(JobUnitInterface::class);

        $output = $this->createMock(OutputInterface::class);

        self::assertInstanceOf(
            DisplayResult::class,
            ($this->buildStep())($manager, $client, $job)
        );
    }

    public function testInvokeWithNoResult()
    {
        $client = $this->createMock(EastClient::class);

        $manager = $this->createMock(ManagerInterface::class);
        $job = $this->createMock(JobUnitInterface::class);

        $this->getDateTimeServiceMock()
            ->expects(self::any())
            ->method('passMeTheDate')
            ->willReturnCallback(function (callable $callback) {
                $callback(new \DateTime('2018-08-01'));

                return $this->getDateTimeServiceMock();
            });

        $this->getNormalizer()
            ->expects(self::once())
            ->method('normalize')
            ->with($result = [])
            ->willReturnCallback(
                function (
                    $object,
                    PromiseInterface $promise
                ) use ($result) {
                    $promise->success($result);

                    return $this->getNormalizer();
                }
            );

        $manager->expects(self::once())
            ->method('updateWorkPlan')
            ->willReturnCallback(function ($values) use ($manager) {
                self::assertInstanceOf(History::class, $values[History::class]);
                self::assertIsString($values['historySerialized']);

                return $manager;
            });

        $output = $this->createMock(OutputInterface::class);

        $output->expects(self::once())
            ->method('writeln');

        self::assertInstanceOf(
            DisplayResult::class,
            ($this->buildStep())($manager, $client, $job, null, null, $output)
        );
    }
}