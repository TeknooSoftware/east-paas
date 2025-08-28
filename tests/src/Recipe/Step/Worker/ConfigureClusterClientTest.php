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

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Teknoo\East\Foundation\Client\ClientInterface as EastClient;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Cluster\Collection;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureClusterClient;
use TypeError;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ConfigureClusterClient::class)]
class ConfigureClusterClientTest extends TestCase
{
    private (Directory&MockObject)|null $clients = null;

    public function getClientsMock(): Directory&MockObject
    {
        if (!$this->clients instanceof Directory) {
            $this->clients = $this->createMock(Directory::class);
        }

        return $this->clients;
    }

    public function buildStep(): ConfigureClusterClient
    {
        return new ConfigureClusterClient(
            $this->getClientsMock(),
        );
    }

    public function testInvokeBadJob(): void
    {
        $this->expectException(TypeError::class);
        ($this->buildStep())(
            new stdClass(),
            $this->createMock(EastClient::class),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadClient(): void
    {
        $this->expectException(TypeError::class);
        ($this->buildStep())(
            $this->createMock(JobUnitInterface::class),
            new stdClass(),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadManager(): void
    {
        $this->expectException(TypeError::class);
        ($this->buildStep())(
            $this->createMock(JobUnitInterface::class),
            $this->createMock(EastClient::class),
            new stdClass()
        );
    }

    public function testInvoke(): void
    {
        $job = $this->createMock(JobUnitInterface::class);
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(EastClient::class);

        $manager->expects($this->once())
            ->method('updateWorkPlan')
            ->with([Collection::class => $this->createMock(Collection::class)]);

        $job
            ->method('configureCluster')
            ->willReturnCallback(
                function (Directory $client, PromiseInterface $promise) use ($job): MockObject {
                    $promise->success($this->createMock(Collection::class));

                    return $job;
                }
            );

        $this->assertInstanceOf(ConfigureClusterClient::class, ($this->buildStep())($job, $client, $manager, $this->createMock(CompiledDeploymentInterface::class)));
    }

    public function testInvokeOnError(): void
    {
        $job = $this->createMock(JobUnitInterface::class);
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(EastClient::class);

        $job
            ->method('configureCluster')
            ->willReturnCallback(
                function (Directory $client, PromiseInterface $promise) use ($job): MockObject {
                    $promise->fail(new Exception());

                    return $job;
                }
            );

        $manager->expects($this->never())
            ->method('updateWorkPlan');

        $manager->expects($this->once())
            ->method('error');

        $this->assertInstanceOf(ConfigureClusterClient::class, ($this->buildStep())($job, $client, $manager, $this->createMock(CompiledDeploymentInterface::class)));
    }
}
