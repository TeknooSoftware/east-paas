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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Compilation\Compiler;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\Compiler\SecretCompiler;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Compilation\Compiler\SecretCompiler
 */
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

    public function testCompileWithoutDefinitions()
    {
        $definitions = [];

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::never())->method('addSecret');

        self::assertInstanceOf(
            SecretCompiler::class,
            $this->buildCompiler()->compile(
                $definitions,
                $compiledDeployment,
                $this->createMock(JobWorkspaceInterface::class),
                $this->createMock(JobUnitInterface::class )
            )
        );
    }

    public function testCompile()
    {
        $definitions = $this->getDefinitionsArray();
        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::exactly(2))->method('addSecret');

        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class );

        self::assertInstanceOf(
            SecretCompiler::class,
            $builder->compile(
                $definitions,
                $compiledDeployment,
                $workspace,
                $jobUnit
            )
        );
    }
}
