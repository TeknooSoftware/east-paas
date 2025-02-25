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
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\Worker;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\ConductorInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\CompileDeployment;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(CompileDeployment::class)]
class CompileDeploymentTest extends TestCase
{
    public function buildStep(): CompileDeployment
    {
        return new CompileDeployment();
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

        $manager->expects($this->once())
            ->method('updateWorkPlan')
            ->with([CompiledDeploymentInterface::class => $compiled,]);

        $conductor->expects($this->once())
            ->method('compileDeployment')
            ->willReturnCallback(
                function (PromiseInterface $promise) use ($conductor, $compiled) {
                    $promise->success($compiled);

                    return $conductor;
                }
            );

        self::assertInstanceOf(
            CompileDeployment::class,
            ($this->buildStep())(
                $manager,
                $client,
                $conductor,
            )
        );
    }

    public function testInvokeOnError()
    {
        $manager = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(ClientInterface::class);
        $conductor = $this->createMock(ConductorInterface::class);
        $compiled = $this->createMock(CompiledDeploymentInterface::class);

        $conductor->expects($this->once())
            ->method('compileDeployment')
            ->willReturnCallback(
                function (PromiseInterface $promise) use ($conductor, $compiled) {
                    $promise->fail(new \Exception());

                    return $conductor;
                }
            );

        $manager->expects($this->never())
            ->method('updateWorkPlan');

        $manager->expects($this->once())
            ->method('error');

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
