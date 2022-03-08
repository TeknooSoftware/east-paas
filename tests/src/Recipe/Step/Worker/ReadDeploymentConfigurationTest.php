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
 * @author      Richard Déloge <richarddeloge@gmail.com>
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

        $this->expectException(\RuntimeException::class);

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
