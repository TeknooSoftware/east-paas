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
use Teknoo\East\Foundation\Http\Message\MessageFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Conductor\ConductorInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\CompileDeployment;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Recipe\Step\Worker\CompileDeployment
 */
class CompileDeploymentTest extends TestCase
{
    public function buildStep(): CompileDeployment
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::any())->method('withAddedHeader')->willReturnSelf();
        $response->expects(self::any())->method('withBody')->willReturnSelf();
        $response->expects(self::any())->method('withStatus')->willReturnSelf();

        $messageFactory = $this->createMock(MessageFactoryInterface::class);
        $messageFactory->expects(self::any())->method('createMessage')->willReturn(
            $response
        );

        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $streamFactory->expects(self::any())->method('createStream')->willReturn(
            $this->createMock(StreamInterface::class)
        );

        return new CompileDeployment(
            $messageFactory,
            $streamFactory
        );
    }

    public function testInvokeBadManager()
    {
        $this->expectException(\TypeError::class);

        ($this->buildStep())(
            new \stdClass(),
            $this->createMock(ConductorInterface::class)
        );
    }

    public function testInvokeBadBuilder()
    {
        $this->expectException(\TypeError::class);

        ($this->buildStep())(
            $this->createMock(ManagerInterface::class),
            new \stdClass()
        );
    }

    public function testInvoke()
    {
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(ClientInterface::class);
        $conductor = $this->createMock(ConductorInterface::class);
        $compiled = $this->createMock(CompiledDeploymentInterface::class);

        $manager->expects(self::once())
            ->method('updateWorkPlan')
            ->with([CompiledDeploymentInterface::class => $compiled,]);

        $conductor->expects(self::once())
            ->method('compileDeployment')
            ->willReturnCallback(
                function (PromiseInterface $promise, $storage) use ($conductor, $compiled) {
                    $promise->success($compiled);
                    self::assertNull($storage);

                    return $conductor;
                }
            );

        self::assertInstanceOf(
            CompileDeployment::class,
            ($this->buildStep())(
                $manager,
                $client,
                $conductor
            )
        );
    }

    public function testInvokeWithStorage()
    {
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(ClientInterface::class);
        $conductor = $this->createMock(ConductorInterface::class);
        $compiled = $this->createMock(CompiledDeploymentInterface::class);

        $manager->expects(self::once())
            ->method('updateWorkPlan')
            ->with([CompiledDeploymentInterface::class => $compiled,]);

        $conductor->expects(self::once())
            ->method('compileDeployment')
            ->willReturnCallback(
                function (PromiseInterface $promise, $storage) use ($conductor, $compiled) {
                    $promise->success($compiled);
                    self::assertEquals('foo', $storage);

                    return $conductor;
                }
            );

        self::assertInstanceOf(
            CompileDeployment::class,
            ($this->buildStep())(
                $manager,
                $client,
                $conductor,
                'foo'
            )
        );
    }

    public function testInvokeOnError()
    {
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(ClientInterface::class);
        $conductor = $this->createMock(ConductorInterface::class);
        $compiled = $this->createMock(CompiledDeploymentInterface::class);

        $conductor->expects(self::once())
            ->method('compileDeployment')
            ->willReturnCallback(
                function (PromiseInterface $promise) use ($conductor, $compiled) {
                    $promise->fail(new \Exception());

                    return $conductor;
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
            CompileDeployment::class,
            ($this->buildStep())(
                $manager,
                $client,
                $conductor
            )
        );
    }
}
