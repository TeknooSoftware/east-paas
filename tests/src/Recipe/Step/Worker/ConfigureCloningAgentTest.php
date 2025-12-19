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
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use stdClass;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureCloningAgent;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use TypeError;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ConfigureCloningAgent::class)]
class ConfigureCloningAgentTest extends TestCase
{
    private (CloningAgentInterface&MockObject)|(CloningAgentInterface&Stub)|null $agent = null;

    public function getAgentMock(bool $stub = false): (CloningAgentInterface&Stub)|(CloningAgentInterface&MockObject)
    {
        if (!$this->agent instanceof CloningAgentInterface) {
            if ($stub) {
                $this->agent = $this->createStub(CloningAgentInterface::class);
            } else {
                $this->agent = $this->createMock(CloningAgentInterface::class);
            }
        }

        return $this->agent;
    }

    public function buildStep(): ConfigureCloningAgent
    {
        return new ConfigureCloningAgent(
            $this->getAgentMock(true),
        );
    }

    public function testInvokeBadJob(): void
    {
        $this->expectException(TypeError::class);
        ($this->buildStep())(
            new stdClass(),
            $this->createStub(JobWorkspaceInterface::class),
            $this->createStub(ClientInterface::class),
            $this->createStub(ManagerInterface::class)
        );
    }

    public function testInvokeBadWorkspace(): void
    {
        $this->expectException(TypeError::class);
        ($this->buildStep())(
            $this->createStub(JobUnitInterface::class),
            new stdClass(),
            $this->createStub(ClientInterface::class),
            $this->createStub(ManagerInterface::class)
        );
    }

    public function testInvokeBadClient(): void
    {
        $this->expectException(TypeError::class);
        ($this->buildStep())(
            $this->createStub(JobUnitInterface::class),
            $this->createStub(JobWorkspaceInterface::class),
            new stdClass(),
            $this->createStub(ManagerInterface::class)
        );
    }

    public function testInvokeBadManager(): void
    {
        $this->expectException(TypeError::class);
        ($this->buildStep())(
            $this->createStub(JobUnitInterface::class),
            $this->createStub(JobWorkspaceInterface::class),
            $this->createStub(ClientInterface::class),
            new stdClass()
        );
    }

    public function testInvoke(): void
    {
        $job = $this->createStub(JobUnitInterface::class);
        $workspace = $this->createStub(JobWorkspaceInterface::class);
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createStub(ClientInterface::class);

        $manager->expects($this->once())
            ->method('updateWorkPlan')
            ->with([CloningAgentInterface::class => $this->getAgentMock(true)]);

        $job
            ->method('configureCloningAgent')
            ->willReturnCallback(
                function (CloningAgentInterface $agent, JobWorkspaceInterface $workspace, PromiseInterface $promise) use ($job): MockObject|Stub {
                    $promise->success(clone $agent);

                    return $job;
                }
            );

        $this->assertInstanceOf(ConfigureCloningAgent::class, ($this->buildStep())($job, $workspace, $client, $manager));
    }

    public function testInvokeOnError(): void
    {
        $job = $this->createStub(JobUnitInterface::class);
        $workspace = $this->createStub(JobWorkspaceInterface::class);
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createStub(ClientInterface::class);

        $job
            ->method('configureCloningAgent')
            ->willReturnCallback(
                function (CloningAgentInterface $agent, JobWorkspaceInterface $workspace, PromiseInterface $promise) use ($job, $manager): MockObject|Stub {
                    $promise->fail(new Exception());

                    return $job;
                }
            );

        $manager->expects($this->never())
            ->method('updateWorkPlan');

        $manager->expects($this->once())
            ->method('error');

        $this->assertInstanceOf(ConfigureCloningAgent::class, ($this->buildStep())($job, $workspace, $client, $manager));
    }
}
