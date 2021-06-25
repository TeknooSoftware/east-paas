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

namespace Teknoo\Tests\East\Paas\Recipe\Step\Worker;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Container\BuilderInterface as ImageBuilder;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureImagesBuilder;
use Teknoo\Tests\East\Paas\ErrorFactory;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Recipe\Step\Worker\ConfigureImagesBuilder
 */
class ConfigureImagesBuilderTest extends TestCase
{
    /**
     * @var ImageBuilder
     */
    private $builder;

    /**
     * @return ImageBuilder|MockObject
     */
    public function getBuilderMock(): ImageBuilder
    {
        if (!$this->builder instanceof ImageBuilder) {
            $this->builder = $this->createMock(ImageBuilder::class);
        }

        return $this->builder;
    }

    public function buildStep(): ConfigureImagesBuilder
    {
        return new ConfigureImagesBuilder(
            $this->getBuilderMock(),
            new ErrorFactory(),
        );
    }

    public function testInvokeBadJob()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(
            new \stdClass(),
            $this->createMock(ClientInterface::class),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadClient()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(
            $this->createMock(JobUnitInterface::class),
            new \stdClass(),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadManager()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(
            $this->createMock(JobUnitInterface::class),
            $this->createMock(ClientInterface::class),
            new \stdClass()
        );
    }

    public function testInvoke()
    {
        $job = $this->createMock(JobUnitInterface::class);
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(ClientInterface::class);

        $manager->expects(self::once())
            ->method('updateWorkPlan')
            ->with([ImageBuilder::class => $this->getBuilderMock()]);

        $job->expects(self::any())
            ->method('configureImageBuilder')
            ->willReturnCallback(
                function ($builder, PromiseInterface $promise) use ($job) {
                    $promise->success(clone $builder);

                    return $job;
                }
            );

        self::assertInstanceOf(
            ConfigureImagesBuilder::class,
            ($this->buildStep())($job, $client, $manager)
        );
    }

    public function testInvokeOnError()
    {
        $job = $this->createMock(JobUnitInterface::class);
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(ClientInterface::class);

        $job->expects(self::any())
            ->method('configureImageBuilder')
            ->willReturnCallback(
                function ($builder, PromiseInterface $promise) use ($job) {
                    $promise->fail(new \Exception());

                    return $job;
                }
            );

        $manager->expects(self::never())
            ->method('updateWorkPlan');

        $manager->expects(self::once())
            ->method('finish')
            ->willReturnSelf();

        $client->expects(self::once())
            ->method('acceptResponse')
            ->willReturnSelf();

        self::assertInstanceOf(
            ConfigureImagesBuilder::class,
            ($this->buildStep())($job, $client, $manager)
        );
    }
}
