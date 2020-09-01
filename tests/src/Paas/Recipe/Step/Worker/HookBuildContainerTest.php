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

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\East\Paas\Recipe\Step\Worker\HookBuildContainer;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Recipe\Step\Worker\HookBuildContainer
 */
class HookBuildContainerTest extends TestCase
{
    public function buildStep(): HookBuildContainer
    {
        return new HookBuildContainer();
    }

    public function testInvokeBadJobWorkspace()
    {
        $this->expectException(\TypeError::class);

        ($this->buildStep())(
            new \stdClass(),
            $this->createMock(CompiledDeployment::class)
        );
    }

    public function testInvokeBadCompiledDeployment()
    {
        $this->expectException(\TypeError::class);

        ($this->buildStep())(
            $this->createMock(JobWorkspaceInterface::class),
            new \stdClass()
        );
    }

    public function testInvoke()
    {
        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $compiled = $this->createMock(CompiledDeployment::class);
        $hook1 = $this->createMock(HookInterface::class);
        $hook1->expects(self::once())->method('setPath')->with('foo/bar');
        $hook1->expects(self::once())->method('run');
        $hook2 = $this->createMock(HookInterface::class);
        $hook2->expects(self::once())->method('setPath')->with('foo/bar');
        $hook2->expects(self::once())->method('run');

        $workspace->expects(self::once())
            ->method('runInRoot')
            ->willReturnCallback(
                function (callable $callback) use ($workspace) {
                    $callback('foo/bar');

                    return $workspace;
                }
            );

        $compiled->expects(self::once())
            ->method('foreachHook')
            ->willReturnCallback(
                function (callable $callback) use ($hook1, $hook2, $compiled) {
                    $callback($hook1);
                    $callback($hook2);

                    return $compiled;
                }
            );

        self::assertInstanceOf(
            HookBuildContainer::class,
            ($this->buildStep())(
                $workspace,
                $compiled
            )
        );
    }
}
