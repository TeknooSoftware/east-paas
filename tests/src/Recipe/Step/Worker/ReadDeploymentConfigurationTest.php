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

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface as EastClient;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Conductor\ConductorInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\ReadDeploymentConfiguration;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\Tests\East\Paas\ErrorFactory;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Recipe\Step\Worker\ReadDeploymentConfiguration
 */
class ReadDeploymentConfigurationTest extends TestCase
{
    public function buildStep(): ReadDeploymentConfiguration
    {
        return new ReadDeploymentConfiguration(
            new ErrorFactory(),
        );
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
            ->method('finish')
            ->willReturnSelf();

        $client->expects(self::once())
            ->method('acceptResponse')
            ->willReturnSelf();

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
