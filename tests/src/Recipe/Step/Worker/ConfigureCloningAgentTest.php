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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureCloningAgent;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Recipe\Step\Worker\ConfigureCloningAgent
 */
class ConfigureCloningAgentTest extends TestCase
{
    /**
     * @var CloningAgentInterface
     */
    private $agent;

    /**
     * @return CloningAgentInterface|MockObject
     */
    public function getAgentMock(): CloningAgentInterface
    {
        if (!$this->agent instanceof CloningAgentInterface) {
            $this->agent = $this->createMock(CloningAgentInterface::class);
        }

        return $this->agent;
    }

    public function buildStep(): ConfigureCloningAgent
    {
        return new ConfigureCloningAgent(
            $this->getAgentMock(),
        );
    }

    public function testInvokeBadJob()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(
            new \stdClass(),
            $this->createMock(JobWorkspaceInterface::class),
            $this->createMock(ClientInterface::class),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadWorkspace()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(
            $this->createMock(JobUnitInterface::class),
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
            $this->createMock(JobWorkspaceInterface::class),
            new \stdClass(),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadManager()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(
            $this->createMock(JobUnitInterface::class),
            $this->createMock(JobWorkspaceInterface::class),
            $this->createMock(ClientInterface::class),
            new \stdClass()
        );
    }

    public function testInvoke()
    {
        $job = $this->createMock(JobUnitInterface::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(ClientInterface::class);

        $manager->expects(self::once())
            ->method('updateWorkPlan')
            ->with([CloningAgentInterface::class => $this->getAgentMock()]);

        $job->expects(self::any())
            ->method('configureCloningAgent')
            ->willReturnCallback(
                function ($agent, $workspace, PromiseInterface $promise) use ($job, $manager) {
                    $promise->success(clone $agent);

                    return $job;
                }
            );

        self::assertInstanceOf(
            ConfigureCloningAgent::class,
            ($this->buildStep())($job, $workspace, $client, $manager)
        );
    }

    public function testInvokeOnError()
    {
        $job = $this->createMock(JobUnitInterface::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(ClientInterface::class);

        $job->expects(self::any())
            ->method('configureCloningAgent')
            ->willReturnCallback(
                function ($agent, $workspace, PromiseInterface $promise) use ($job, $manager) {
                    $promise->fail(new \Exception());

                    return $job;
                }
            );

        $manager->expects(self::never())
            ->method('updateWorkPlan');

        $this->expectException(\RuntimeException::class);

        self::assertInstanceOf(
            ConfigureCloningAgent::class,
            ($this->buildStep())($job, $workspace, $client, $manager)
        );
    }
}
