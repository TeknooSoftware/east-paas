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
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\Worker;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureCloningAgent;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @covers \Teknoo\East\Paas\Recipe\Step\Worker\ConfigureCloningAgent
 * @covers \Teknoo\East\Paas\Recipe\Traits\ErrorTrait
 * @covers \Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait
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
        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::any())->method('withAddedHeader')->willReturnSelf();
        $response->expects(self::any())->method('withBody')->willReturnSelf();

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory->expects(self::any())->method('createResponse')->willReturn(
            $response
        );

        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $streamFactory->expects(self::any())->method('createStream')->willReturn(
            $this->createMock(StreamInterface::class)
        );

        return new ConfigureCloningAgent(
            $this->getAgentMock(),
            $responseFactory,
            $streamFactory
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

        $manager->expects(self::once())
            ->method('finish')
            ->willReturnSelf();

        $client->expects(self::once())
            ->method('acceptResponse')
            ->willReturnSelf();

        self::assertInstanceOf(
            ConfigureCloningAgent::class,
            ($this->buildStep())($job, $workspace, $client, $manager)
        );
    }
}
