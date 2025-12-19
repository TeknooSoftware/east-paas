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

namespace Teknoo\Tests\East\Paas\Compilation\Compiler;

use ArrayIterator;
use DomainException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\Compiler\HookCompiler;
use Teknoo\East\Paas\Compilation\Compiler\ResourceManager;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(HookCompiler::class)]
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

    public function testCompileHookNotPresent(): void
    {
        $this->expectException(DomainException::class);

        $definitions = $this->getDefinitionsArray();

        $hook = $this->createStub(HookInterface::class);

        $hookCompiler = new HookCompiler(
            [
                'foo' => $hook
            ]
        );

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->never())->method('addHook');

        $this->assertInstanceOf(HookCompiler::class, $hookCompiler->compile(
            $definitions,
            $compiledDeployment,
            $this->createStub(JobWorkspaceInterface::class),
            $this->createStub(JobUnitInterface::class),
            $this->createStub(ResourceManager::class),
            $this->createStub(DefaultsBag::class),
        ));
    }

    public function testCompileHookError(): void
    {
        $this->expectException(Exception::class);

        $definitions = $this->getDefinitionsArray();

        $hook = $this->createStub(HookInterface::class);
        $hook
            ->method('setOptions')
            ->willReturnCallback(function (array $options, PromiseInterface $promise) use ($hook): MockObject|Stub {
                $promise->fail(new Exception());

                return $hook;
            });

        $hookCompiler = new HookCompiler(
            [
                'composer' => $hook
            ]
        );

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->never())->method('addHook');

        $this->assertInstanceOf(HookCompiler::class, $hookCompiler->compile(
            $definitions,
            $compiledDeployment,
            $this->createStub(JobWorkspaceInterface::class),
            $this->createStub(JobUnitInterface::class),
            $this->createStub(ResourceManager::class),
            $this->createStub(DefaultsBag::class),
        ));
    }

    public function testCompileWithArrayLibrary(): void
    {
        $definitions = $this->getDefinitionsArray();

        $hook = $this->createStub(HookInterface::class);
        $hook
            ->method('setOptions')
            ->willReturnSelf();

        $hookCompiler = new HookCompiler(
            [
                'composer' => $hook
            ]
        );

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->once())->method('addHook');

        $this->assertInstanceOf(HookCompiler::class, $hookCompiler->compile(
            $definitions,
            $compiledDeployment,
            $this->createStub(JobWorkspaceInterface::class),
            $this->createStub(JobUnitInterface::class),
            $this->createStub(ResourceManager::class),
            $this->createStub(DefaultsBag::class),
        ));
    }

    public function testCompileWithoutDefinitions(): void
    {
        $definitions = [];
        $hookCompiler = new HookCompiler(
            new ArrayIterator([
                'composer' => $this->createStub(HookInterface::class)
            ])
        );

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->never())->method('addHook');

        $this->assertInstanceOf(HookCompiler::class, $hookCompiler->compile(
            $definitions,
            $compiledDeployment,
            $this->createStub(JobWorkspaceInterface::class),
            $this->createStub(JobUnitInterface::class),
            $this->createStub(ResourceManager::class),
            $this->createStub(DefaultsBag::class),
        ));
    }

    public function testCompileWithIteratorLibrary(): void
    {
        $definitions = $this->getDefinitionsArray();

        $hook = $this->createStub(HookInterface::class);
        $hook
            ->method('setOptions')
            ->willReturnSelf();

        $hookCompiler = new HookCompiler(
            new ArrayIterator([
                'composer' => $hook
            ])
        );

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->once())->method('addHook');

        $this->assertInstanceOf(HookCompiler::class, $hookCompiler->compile(
            $definitions,
            $compiledDeployment,
            $this->createStub(JobWorkspaceInterface::class),
            $this->createStub(JobUnitInterface::class),
            $this->createStub(ResourceManager::class),
            $this->createStub(DefaultsBag::class),
        ));
    }
}
