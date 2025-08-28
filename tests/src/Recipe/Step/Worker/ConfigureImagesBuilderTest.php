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

namespace Teknoo\Tests\East\Paas\Recipe\Step\Worker;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuilderInterface as ImageBuilder;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureImagesBuilder;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ConfigureImagesBuilder::class)]
class ConfigureImagesBuilderTest extends TestCase
{
    private (ImageBuilder&MockObject)|null $builder = null;

    public function getBuilderMock(): ImageBuilder&MockObject
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
        );
    }

    public function testInvokeBadJob(): void
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(
            new \stdClass(),
            $this->createMock(ClientInterface::class),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadClient(): void
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(
            $this->createMock(JobUnitInterface::class),
            new \stdClass(),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadManager(): void
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(
            $this->createMock(JobUnitInterface::class),
            $this->createMock(ClientInterface::class),
            new \stdClass()
        );
    }

    public function testInvoke(): void
    {
        $job = $this->createMock(JobUnitInterface::class);
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(ClientInterface::class);

        $manager->expects($this->once())
            ->method('updateWorkPlan')
            ->with([ImageBuilder::class => $this->getBuilderMock()]);

        $job
            ->method('configureImageBuilder')
            ->willReturnCallback(
                function (ImageBuilder $builder, PromiseInterface $promise) use ($job): MockObject {
                    $promise->success(clone $builder);

                    return $job;
                }
            );

        $this->assertInstanceOf(ConfigureImagesBuilder::class, ($this->buildStep())($job, $client, $manager));
    }

    public function testInvokeOnError(): void
    {
        $job = $this->createMock(JobUnitInterface::class);
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(ClientInterface::class);

        $job
            ->method('configureImageBuilder')
            ->willReturnCallback(
                function (ImageBuilder $builder, PromiseInterface $promise) use ($job): MockObject {
                    $promise->fail(new \Exception());

                    return $job;
                }
            );

        $manager->expects($this->never())
            ->method('updateWorkPlan');

        $manager->expects($this->once())
            ->method('error');

        $this->assertInstanceOf(ConfigureImagesBuilder::class, ($this->buildStep())($job, $client, $manager));
    }
}
