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
use Teknoo\East\Paas\Compilation\Compiler\SecretCompiler;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(SecretCompiler::class)]
class SecretCompilerTest extends TestCase
{
    public function buildCompiler(): SecretCompiler
    {
        return new SecretCompiler();
    }

    private function getDefinitionsArray(): array
    {
        return [
            'demo_vault' => [
                'provider' => 'hashicorp/vault',
                'options' => [
                    'server' => 'vault.teknoo.software',
                ],
            ],
            'map_vault' => [
                'provider' => 'map',
                'options' => [
                    'key1' =>  'value1',
                    'key2' =>  'foo',
                ]
            ],
        ];
    }

    public function testCompileWithoutDefinitions(): void
    {
        $definitions = [];

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->never())->method('addSecret');

        $this->assertInstanceOf(SecretCompiler::class, $this->buildCompiler()->compile(
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
        $compiledDeployment->expects($this->exactly(2))->method('addSecret');

        $workspace = $this->createStub(JobWorkspaceInterface::class);
        $jobUnit = $this->createStub(JobUnitInterface::class);

        $this->assertInstanceOf(SecretCompiler::class, $builder->compile(
            $definitions,
            $compiledDeployment,
            $workspace,
            $jobUnit,
            $this->createStub(ResourceManager::class),
            $this->createStub(DefaultsBag::class),
        ));
    }
}
