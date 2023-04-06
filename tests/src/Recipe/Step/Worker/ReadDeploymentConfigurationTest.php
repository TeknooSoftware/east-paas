<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\Worker;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface as EastClient;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\ConductorInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\ReadDeploymentConfiguration;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Recipe\Step\Worker\ReadDeploymentConfiguration
 */
class ReadDeploymentConfigurationTest extends TestCase
{
    public function buildStep(): ReadDeploymentConfiguration
    {
        return new ReadDeploymentConfiguration();
    }

    public function testInvoke()
    {
        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $conductor = $this->createMock(ConductorInterface::class);
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(EastClient::class);

        $workspace->expects(self::once())
            ->method('loadDeploymentIntoConductor')
            ->with($conductor);

        self::assertInstanceOf(
            ReadDeploymentConfiguration::class,
            ($this->buildStep())(
                $workspace,
                $conductor,
                $client,
                $manager
            )
        );
    }

    public function testInvokeOnError()
    {
        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $conductor = $this->createMock(ConductorInterface::class);
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(EastClient::class);

        $workspace->expects(self::once())
            ->method('loadDeploymentIntoConductor')
            ->with($conductor)
            ->willReturnCallback(
                function ($conductor, PromiseInterface $promise) use ($workspace) {
                    $promise->fail(new \Exception());

                    return $workspace;
                }
            );

        $manager->expects(self::never())
            ->method('updateWorkPlan');

        $manager->expects(self::once())
            ->method('error');

        self::assertInstanceOf(
            ReadDeploymentConfiguration::class,
            ($this->buildStep())(
                $workspace,
                $conductor,
                $client,
                $manager
            )
        );
    }

    public function testInvokeOnErrorWithMessage()
    {
        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $conductor = $this->createMock(ConductorInterface::class);
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(EastClient::class);

        $workspace->expects(self::once())
            ->method('loadDeploymentIntoConductor')
            ->with($conductor)
            ->willReturnCallback(
                function ($conductor, PromiseInterface $promise) use ($workspace) {
                    $promise->fail(new \Exception('foo', 400));

                    return $workspace;
                }
            );

        $manager->expects(self::never())
            ->method('updateWorkPlan');

        $manager->expects(self::once())
            ->method('error');

        self::assertInstanceOf(
            ReadDeploymentConfiguration::class,
            ($this->buildStep())(
                $workspace,
                $conductor,
                $client,
                $manager
            )
        );
    }
}
