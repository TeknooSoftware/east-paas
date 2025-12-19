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
use Teknoo\East\Paas\Compilation\Compiler\ResourceManager;
use Teknoo\East\Paas\Compilation\Compiler\VolumeCompiler;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(VolumeCompiler::class)]
class VolumeCompilerTest extends TestCase
{
    public function buildCompiler(): VolumeCompiler
    {
        return new VolumeCompiler();
    }

    private function getDefinitionsArray(): array
    {
        return [
            'main' => [
                'target' => '/opt/paas/',
                'add' => [
                    'src',
                    'vendor',
                    'composer.json',
                    'composer.lock',
                    'composer.phar',
                ],
            ],
        ];
    }

    public function testCompileWithoutDefinitions(): void
    {
        $definitions = [];

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->never())->method('addVolume');

        $this->assertInstanceOf(VolumeCompiler::class, $this->buildCompiler()->compile(
            $definitions,
            $compiledDeployment,
            $this->createStub(JobWorkspaceInterface::class),
            $this->createStub(JobUnitInterface::class),
            $this->createStub(ResourceManager::class),
            $this->createStub(DefaultsBag::class),
        ));
    }

    public function testCompile(): void
    {
        $definitions = $this->getDefinitionsArray();
        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->once())->method('addVolume');

        $workspace = $this->createStub(JobWorkspaceInterface::class);
        $jobUnit = $this->createStub(JobUnitInterface::class);

        $this->assertInstanceOf(VolumeCompiler::class, $builder->compile(
            $definitions,
            $compiledDeployment,
            $workspace,
            $jobUnit,
            $this->createStub(ResourceManager::class),
            $this->createStub(DefaultsBag::class),
        ));
    }
}
