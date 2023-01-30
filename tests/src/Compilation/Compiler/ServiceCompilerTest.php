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

use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Service;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Transport;
use Teknoo\East\Paas\Compilation\Compiler\ServiceCompiler;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Compilation\Compiler\ServiceCompiler
 * @covers \Teknoo\East\Paas\Compilation\Compiler\MergeTrait
 */
class ServiceCompilerTest extends TestCase
{
    public function buildCompiler(): ServiceCompiler
    {
        return new ServiceCompiler(
            [
                'foo-ext' => [
                    'ports' => [
                        [
                            'listen' => 80,
                            'target' => 8080,
                        ],
                    ],
                ],
            ],
        );
    }

    private function getDefinitionsArray(): array
    {
        return [
            'php-react' => [
                'internal' => false,
                'ports' => [
                    [
                        'listen' => 80,
                        'target' => 8080,
                    ],
                ],
            ],
            'php-internal' => [
                'internal' => true,
                'ports' => [
                    [
                        'listen' => 80,
                        'target' => 8080,
                    ],
                ],
            ],
            'php-udp' => [
                'pod' => 'php-react',
                'protocol' => Transport::Udp->value,
                'ports' => [
                    [
                        'listen' => 81,
                        'target' => 8181,
                    ],
                ],
            ],
        ];
    }

    public function testCompileWithoutDefinitions()
    {
        $definitions = [];

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::never())->method('addService');

        self::assertInstanceOf(
            ServiceCompiler::class,
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
        $compiledDeployment
            ->expects(self::exactly(3))
            ->method('addService')
            ->withConsecutive(
                [
                    'php-react',
                    new Service(
                        'php-react',
                        'php-react',
                        [80 => 8080],
                        Transport::Tcp,
                        false,
                    ),
                ],
                [
                    'php-internal',
                    new Service(
                        'php-internal',
                        'php-internal',
                        [80 => 8080],
                        Transport::Tcp,
                        true,
                    ),
                ],
                [
                    'php-udp',
                    new Service(
                        'php-udp',
                        'php-react',
                        [81 => 8181],
                        Transport::Udp,
                        true,
                    ),
                ],
            )
        ;

        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class );

        self::assertInstanceOf(
            ServiceCompiler::class,
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
            'php-react' => [
                'extends' => new stdClass(),
                'internal' => false,
                'ports' => [
                    [
                        'listen' => 80,
                        'target' => 8080,
                    ],
                ],
            ]
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
            'php-react' => [
                'extends' => 'other',
                'internal' => false,
                'ports' => [
                    [
                        'listen' => 80,
                        'target' => 8080,
                    ],
                ],
            ]
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
            'php-react' => [
                'extends' => 'foo-ext',
                'internal' => false,
            ],
            'php-internal' => [
                'extends' => 'foo-ext',
                'internal' => true,
                'ports' => [
                    [
                        'listen' => 1234,
                    ],
                ],
            ],
            'php-filled' => [
                'internal' => true,
                'ports' => [
                    [
                        'listen' => 1234,
                        'target' => 8080,
                    ],
                ],
            ],
        ];
        $builder = $this->buildCompiler();

        self::assertInstanceOf(
            ServiceCompiler::class,
            $builder->extends(
                $definitions,
            )
        );

        self::assertEquals(
            $definitions,
            [
                'php-react' => [
                    'extends' => 'foo-ext',
                    'internal' => false,
                    'ports' => [
                        [
                            'listen' => 80,
                            'target' => 8080,
                        ],
                    ],
                ],
                'php-internal' => [
                    'extends' => 'foo-ext',
                    'internal' => true,
                    'ports' => [
                        [
                            'listen' => 1234,
                            'target' => 8080,
                        ],
                    ],
                ],
                'php-filled' => [
                    'internal' => true,
                    'ports' => [
                        [
                            'listen' => 1234,
                            'target' => 8080,
                        ],
                    ],
                ],
            ]
        );
    }
}
