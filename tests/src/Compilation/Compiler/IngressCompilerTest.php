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

use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Teknoo\East\Paas\Compilation\Compiler\IngressCompiler;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Compilation\Compiler\IngressCompiler
 * @covers \Teknoo\East\Paas\Compilation\Compiler\MergeTrait
 */
class IngressCompilerTest extends TestCase
{
    public function buildCompiler(): IngressCompiler
    {
        return new IngressCompiler(
            [
                'foo-ext' => [
                    'host' => 'demo2-paas.teknoo.software',
                    'tls' => [
                        'cert' => 'foo',
                        'key' => 'bar',
                    ],
                ]
            ]
        );
    }

    private function getDefinitionsArray(): array
    {
        return [
            'demo' => [
                'host' => 'demo-paas.teknoo.software',
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
            'demo-secure' => [
                'host' => 'demo-paas.teknoo.software',
                'https-backend' => true,
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
        $compiledDeployment->expects(self::exactly(2))->method('addIngress');

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
        $compiledDeployment->expects(self::exactly(2))->method('addIngress');

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

    public function testCompileWithWrongExtends()
    {
        $definitions = [
            'demo' => [
                'extends' => new stdClass(),
                'host' => 'demo-paas.teknoo.software',
                'tls' => [
                    'cert' => 'foo',
                    'key' => 'bar',
                ],
                'service' => [
                    'name' => 'php-react',
                    'port' => 80
                ],
            ],
        ];
        $builder = $this->buildCompiler();

        $this->expectException(InvalidArgumentException::class);
        $builder->extends(
            $definitions,
        );
    }

    public function testCompileWithNonExistantExtends()
    {
        $definitions = [
            'demo' => [
                'extends' => 'other',
                'host' => 'demo-paas.teknoo.software',
                'tls' => [
                    'cert' => 'foo',
                    'key' => 'bar',
                ],
                'service' => [
                    'name' => 'php-react',
                    'port' => 80
                ],
            ],
        ];
        $builder = $this->buildCompiler();

        $this->expectException(DomainException::class);
        $builder->extends(
            $definitions,
        );
    }

    public function testCompileWithExtends()
    {
        $definitions = [
            'demo' => [
                'extends' => 'foo-ext',
            ],
            'demo-secure' => [
                'host' => 'demo-paas.teknoo.software',
                'extends' => 'foo-ext',
            ],
            'demo3-secure' => [
                'host' => 'demo-paas3.teknoo.software',
                'tls' => [
                    'cert' => 'foo',
                    'key' => 'bar',
                ],
            ],
        ];
        $builder = $this->buildCompiler();

        self::assertInstanceOf(
            IngressCompiler::class,
            $builder->extends(
                $definitions,
            )
        );

        self::assertEquals(
            $definitions,
            [
                'demo' => [
                    'extends' => 'foo-ext',
                    'host' => 'demo2-paas.teknoo.software',
                    'tls' => [
                        'cert' => 'foo',
                        'key' => 'bar',
                    ],
                ],
                'demo-secure' => [
                    'host' => 'demo-paas.teknoo.software',
                    'extends' => 'foo-ext',
                    'tls' => [
                        'cert' => 'foo',
                        'key' => 'bar',
                    ],
                ],
                'demo3-secure' => [
                    'host' => 'demo-paas3.teknoo.software',
                    'tls' => [
                        'cert' => 'foo',
                        'key' => 'bar',
                    ],
                ],
            ]
        );
    }
}
