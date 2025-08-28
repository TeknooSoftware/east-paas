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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\Compiler\DefaultsCompiler;
use Teknoo\East\Paas\Compilation\Compiler\ResourceManager;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(DefaultsCompiler::class)]
class DefaultsCompilerTest extends TestCase
{
    public function buildCompiler(
        ?string $storageIdentifier,
        ?string $storageSize,
        ?string $defaultOciRegistryConfig,
    ): DefaultsCompiler {
        return new DefaultsCompiler(
            storageIdentifier: $storageIdentifier,
            storageSize: $storageSize,
            defaultOciRegistryConfig: $defaultOciRegistryConfig,
        );
    }

    public function testCompileWithoutAnyDefaults(): void
    {
        $definitions = [];

        $bag = $this->createMock(DefaultsBag::class);
        $bag->expects($this->once())
            ->method('set')
            ->with('oci-registry-config-name', null)
            ->willReturnSelf();

        $this->assertInstanceOf(DefaultsCompiler::class, $this->buildCompiler(null, null, null)->compile(
            $definitions,
            $this->createMock(CompiledDeploymentInterface::class),
            $this->createMock(JobWorkspaceInterface::class),
            $this->createMock(JobUnitInterface::class),
            $this->createMock(ResourceManager::class),
            $bag,
        ));
    }

    public function testCompileWithDefaultsFromConstructor(): void
    {
        $definitions = [];

        $bag = $this->createMock(DefaultsBag::class);
        $bag->expects($this->exactly(3))
            ->method('set')
            ->willReturnSelf();

        $this->assertInstanceOf(DefaultsCompiler::class, $this->buildCompiler('foo', 'foo', 'foo')->compile(
            $definitions,
            $this->createMock(CompiledDeploymentInterface::class),
            $this->createMock(JobWorkspaceInterface::class),
            $this->createMock(JobUnitInterface::class),
            $this->createMock(ResourceManager::class),
            $bag,
        ));
    }

    public function testCompileWithoutCluster(): void
    {
        $definitions = [
            'storage-provider' => 'bar',
            'storage-size' => 'bar',
            'oci-registry-config-name' => 'bar',
        ];

        $bag = $this->createMock(DefaultsBag::class);
        $bag->expects($this->exactly(6))
            ->method('set')
            ->willReturnSelf();

        $this->assertInstanceOf(DefaultsCompiler::class, $this->buildCompiler('foo', 'foo', 'foo')->compile(
            $definitions,
            $this->createMock(CompiledDeploymentInterface::class),
            $this->createMock(JobWorkspaceInterface::class),
            $this->createMock(JobUnitInterface::class),
            $this->createMock(ResourceManager::class),
            $bag,
        ));
    }

    public function testCompileWithCluster(): void
    {
        $definitions = [
            'storage-provider' => 'bar',
            'storage-size' => 'bar',
            'oci-registry-config-name' => 'bar',
            'clusters' => [
                'bar' => [
                    'storage-provider' => 'foo',
                ]
            ]
        ];

        $bag = $this->createMock(DefaultsBag::class);
        $bag->expects($this->exactly(7))
            ->method('set')
            ->willReturnSelf();

        $bag->expects($this->once())
            ->method('forCluster')
            ->with('bar')
            ->willReturnSelf();

        $this->assertInstanceOf(DefaultsCompiler::class, $this->buildCompiler('foo', 'foo', 'foo')->compile(
            $definitions,
            $this->createMock(CompiledDeploymentInterface::class),
            $this->createMock(JobWorkspaceInterface::class),
            $this->createMock(JobUnitInterface::class),
            $this->createMock(ResourceManager::class),
            $bag,
        ));
    }
}
