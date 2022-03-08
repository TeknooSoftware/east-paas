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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Compilation\Compiler;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\Compiler\IngressCompiler;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Compilation\Compiler\IngressCompiler
 */
class IngressCompilerTest extends TestCase
{
    public function buildCompiler(): IngressCompiler
    {
        return new IngressCompiler();
    }

    private function getDefinitionsArray(): array
    {
        return [
            'demo' => [
                'host' => 'demo-paas.teknoo.io',
                'tls' => [
                    'cert' => 'foo',
                    'key' => 'bar',
                ],
                'service' => [
                    'name' => 'php-react',
                    'port' => 80
                ],
                'paths' => [
                    [
                        'path' => '/demo',
                        'service' => [
                            'name' => 'php-udp',
                            'port' => 8080,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testCompileWithoutDefinitions()
    {
        $definitions = [];

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::never())->method('addIngress');

        self::assertInstanceOf(
            IngressCompiler::class,
            $this->buildCompiler()->compile(
                $definitions,
                $compiledDeployment,
                $this->createMock(JobWorkspaceInterface::class),
                $this->createMock(JobUnitInterface::class )
            )
        );
    }

    public function testCompilationWithTls()
    {
        $definitions = $this->getDefinitionsArray();
        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::once())->method('addIngress');

        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $jobUnit = $this->createMock(JobUnitInterface::class );

        self::assertInstanceOf(
            IngressCompiler::class,
            $builder->compile(
                $definitions,
                $compiledDeployment,
                $workspace,
                $jobUnit
            )
        );
    }

    public function testCompilationWithoutTls()
    {
        $definitions = $this->getDefinitionsArray();
        unset($definitions['demo']['tls']);

        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::once())->method('addIngress');

        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $jobUnit = $this->createMock(JobUnitInterface::class );

        self::assertInstanceOf(
            IngressCompiler::class,
            $builder->compile(
                $definitions,
                $compiledDeployment,
                $workspace,
                $jobUnit
            )
        );
    }
}
