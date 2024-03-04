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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Compilation\Compiler;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\Compiler\ResourceManager;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Compilation\Compiler\HookCompiler;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Compilation\Compiler\HookCompiler
 */
class HookCompilerTest extends TestCase
{
    private function getDefinitionsArray(): array
    {
        return [
            'composer-init' => [
                'composer' => 'fooo',
            ],
        ];
    }

    public function testCompileHookNotPresent()
    {
        $this->expectException(\DomainException::class);

        $definitions = $this->getDefinitionsArray();

        $hook = $this->createMock(HookInterface::class);

        $hookCompiler = new HookCompiler(
            [
                'foo' => $hook
            ]
        );

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::never())->method('addHook');

        self::assertInstanceOf(
            HookCompiler::class,
            $hookCompiler->compile(
                $definitions,
                $compiledDeployment,
                $this->createMock(JobWorkspaceInterface::class),
                $this->createMock(JobUnitInterface::class),
                $this->createMock(ResourceManager::class),
            )
        );
    }

    public function testCompileHookError()
    {
        $this->expectException(\Exception::class);

        $definitions = $this->getDefinitionsArray();

        $hook = $this->createMock(HookInterface::class);
        $hook->expects(self::any())
            ->method('setOptions')
            ->willReturnCallback(function (array $options, PromiseInterface $promise) use ($hook) {
                $promise->fail(new \Exception());

                return $hook;
            });

        $hookCompiler = new HookCompiler(
            [
                'composer' => $hook
            ]
        );

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::never())->method('addHook');

        self::assertInstanceOf(
            HookCompiler::class,
            $hookCompiler->compile(
                $definitions,
                $compiledDeployment,
                $this->createMock(JobWorkspaceInterface::class),
                $this->createMock(JobUnitInterface::class),
                $this->createMock(ResourceManager::class),
            )
        );
    }

    public function testCompileWithArrayLibrary()
    {
        $definitions = $this->getDefinitionsArray();

        $hook = $this->createMock(HookInterface::class);
        $hook->expects(self::any())
            ->method('setOptions')
            ->willReturnSelf();

        $hookCompiler = new HookCompiler(
            [
                'composer' => $hook
            ]
        );

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::once())->method('addHook');

        self::assertInstanceOf(
            HookCompiler::class,
            $hookCompiler->compile(
                $definitions,
                $compiledDeployment,
                $this->createMock(JobWorkspaceInterface::class),
                $this->createMock(JobUnitInterface::class),
                $this->createMock(ResourceManager::class),
            )
        );
    }

    public function testCompileWithoutDefinitions()
    {
        $definitions = [];
        $hookCompiler = new HookCompiler(
            new \ArrayIterator([
                'composer' => $this->createMock(HookInterface::class)
            ])
        );

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::never())->method('addHook');

        self::assertInstanceOf(
            HookCompiler::class,
            $hookCompiler->compile(
                $definitions,
                $compiledDeployment,
                $this->createMock(JobWorkspaceInterface::class),
                $this->createMock(JobUnitInterface::class),
                $this->createMock(ResourceManager::class),
            )
        );
    }

    public function testCompileWithIteratorLibrary()
    {
        $definitions = $this->getDefinitionsArray();

        $hook = $this->createMock(HookInterface::class);
        $hook->expects(self::any())
            ->method('setOptions')
            ->willReturnSelf();

        $hookCompiler = new HookCompiler(
            new \ArrayIterator([
                'composer' => $hook
            ])
        );

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::once())->method('addHook');

        self::assertInstanceOf(
            HookCompiler::class,
            $hookCompiler->compile(
                $definitions,
                $compiledDeployment,
                $this->createMock(JobWorkspaceInterface::class),
                $this->createMock(JobUnitInterface::class),
                $this->createMock(ResourceManager::class),
            )
        );
    }
}
