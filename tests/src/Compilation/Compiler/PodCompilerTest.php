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

use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\Compiler\PodCompiler;
use Teknoo\East\Paas\Compilation\Compiler\ResourceManager;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\VolumeInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(PodCompiler::class)]
class PodCompilerTest extends TestCase
{
    public function buildCompiler(): PodCompiler
    {
        return new PodCompiler(
            [
                'foo-ext' => [
                    'replicas' => 2,
                    'oci-registry-config-name' => 'bar',
                    'upgrade' => [
                        'max-upgrading-pods' => 2,
                        'max-unavailable-pods' => 1,
                    ],
                ],
            ],
            [
                'bar-ext' => [
                    'image' => 'mongo-react',
                ]
            ]
        );
    }

    private function getDefinitionsArray(): array
    {
        return [
            'node-pod' => [
                'replicas' => 1,
                'oci-registry-config-name' => 'foo',
                'upgrade' => [
                    'max-upgrading-pods' => 2,
                    'max-unavailable-pods' => 1,
                ],
                'restart-policy' => 'always',
                'containers' => [
                    'node-react' => [
                        'image' => 'node-react',
                        'version' => 123,
                        'listen' => [8181],
                        'healthcheck' => [
                            'initial-delay-seconds' => 10,
                            'period-seconds' => 30,
                            'probe' => [
                                'http' => [
                                    'port' => 8080,
                                    'path' => '/status',
                                ],
                            ],
                            'threshold' => [
                                'success' => 23,
                                'failure' => 45,
                            ],
                        ],
                        'resources' => [
                            [
                                'type' => 'cpu',
                                'require' => '200m',
                                'limit' => '400m',
                            ],
                            [
                                'type' => 'memory',
                                'require' => '128Mi',
                                'limit' => '512Mi',
                            ]
                        ]
                    ],
                    'waf' => [
                        'image' => 'nginx-waf',
                        'version' => 1,
                        'listen' => [8181],
                        'healthcheck' => [
                            'initial-delay-seconds' => 10,
                            'period-seconds' => 30,
                            'probe' => [
                                'tcp' => [
                                    'port' => 8080,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'shell' => [
                'replicas' => 1,
                'upgrade' => [
                    'strategy' => 'recreate',
                ],
                'requires' => 'x86_64',
                'containers' => [
                    'php-react' => [
                        'image' => 'php-react',
                        'version' => 7.4,
                    ],
                ],
            ],
            'php-pod' => [
                'replicas' => 1,
                'security' => [
                    'fs-group' => 1234,
                ],
                'containers' => [
                    'php-react' => [
                        'image' => 'php-react',
                        'version' => 7.4,
                        'listen' => [8080],
                        'healthcheck' => [
                            'initial-delay-seconds' => 10,
                            'period-seconds' => 30,
                            'probe' => [
                                'command' => [
                                    'ps',
                                    'aux',
                                    'php',
                                ],
                            ],
                        ],
                    ],
                    'php-composer' => [
                        'image' => 'registry/lib/php-composer',
                        'variables' => [
                            'from-secrets' => [
                                'bar' => 'myvauult.key',
                            ],
                            'import-secrets' => [
                                'vault2'
                            ],
                            'from-maps' => [
                                'foo' => 'amap.key',
                            ],
                            'import-maps' => [
                                'map2'
                            ],
                            'foo' => 'bar'
                        ],
                        'version' => 7.4,
                        'volumes' => [
                            'persistent_volume' => [
                                'persistent' => true,
                                'write-many' => false,
                                'mount-path' => '/app/persistent/',
                            ],
                            'resetable_persistent_volume' => [
                                'persistent' => true,
                                'mount-path' => '/app/persistent/',
                                'reset-on-deployment' => true,
                            ],
                            'embedded' => [
                                'add' => [
                                    'foo',
                                    'bar',
                                ],
                                'writables' => [
                                    'bar/*',
                                ],
                                'mount-path' => '/app/embedded/',
                            ],
                            'embedded2' => [
                                'add' => [
                                    'foo',
                                ],
                                'mount-path' => '/app/embedded2/',
                            ],
                            'other_name2' => [
                                'from' => 'main',
                                'mount-path' => '/app/vendor/',
                            ],
                            'map' => [
                                'from-map' => 'a-map',
                                'mount-path' => '/app/vendor/',
                            ],
                            'vault' => [
                                'from-secret' => 'vault',
                                'mount-path' => '/app/vendor/',
                            ],
                        ]
                    ],
                ],
            ],
        ];
    }

    public function testCompileWithoutDefinitions(): void
    {
        $definitions = [];

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->never())->method('addPod');

        $this->assertInstanceOf(PodCompiler::class, $this->buildCompiler()->compile(
            $definitions,
            $compiledDeployment,
            $this->createMock(JobWorkspaceInterface::class),
            $this->createMock(JobUnitInterface::class),
            $this->createMock(ResourceManager::class),
            $this->createMock(DefaultsBag::class),
        ));
    }

    public function testCompile(): void
    {
        $definitions = $this->getDefinitionsArray();
        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->exactly(3))->method('addPod');
        $compiledDeployment->expects($this->exactly(1))
            ->method('importVolume')
            ->willReturnCallback(
                function (
                    string $volumeFrom,
                    string $mountPath,
                    PromiseInterface $promise,
                ) use ($compiledDeployment): MockObject {
                    $promise->success($this->createMock(VolumeInterface::class));

                    return $compiledDeployment;
                }
            );

        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class);

        $this->assertInstanceOf(PodCompiler::class, $builder->compile(
            $definitions,
            $compiledDeployment,
            $workspace,
            $jobUnit,
            $this->createMock(ResourceManager::class),
            $this->createMock(DefaultsBag::class),
        ));
    }

    public function testCompileWithInvalidVolume(): void
    {
        $definitions = $this->getDefinitionsArray();
        unset($definitions['node-pod']);
        unset($definitions['shell']);
        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->never())->method('addPod');
        $compiledDeployment
            ->method('importVolume')
            ->willReturnCallback(
                function (string $volumeFrom, string $mountPath, PromiseInterface $promise) use ($compiledDeployment): MockObject {
                    $promise->fail(new DomainException('foo'));

                    return $compiledDeployment;
                }
            );

        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class);

        $this->expectException(DomainException::class);

        $this->assertInstanceOf(PodCompiler::class, $builder->compile(
            $definitions,
            $compiledDeployment,
            $workspace,
            $jobUnit,
            $this->createMock(ResourceManager::class),
            $this->createMock(DefaultsBag::class),
        ));
    }

    public function testCompileDeploymentWithVolumeWithoutMountPath(): void
    {
        $this->expectException(RuntimeException::class);

        $definitions = $this->getDefinitionsArray();
        unset($definitions['php-pod']['containers']['php-composer']['volumes']['embedded']['mount-path']);
        unset($definitions['php-pod']['containers']['php-composer']['volumes']['persistent_volume']['mount-path']);
        unset($definitions['php-pod']['containers']['php-composer']['volumes']['other_name2']['mount-path']);
        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->method('addPod');

        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class);

        $this->assertInstanceOf(PodCompiler::class, $builder->compile(
            $definitions,
            $compiledDeployment,
            $workspace,
            $jobUnit,
            $this->createMock(ResourceManager::class),
            $this->createMock(DefaultsBag::class),
        ));
    }

    public function testCompileWithWrongExtends(): void
    {
        $definitions = [
            'shell' => [
                'extends' => new stdClass(),
                'upgrade' => [
                    'strategy' => 'recreate',
                ],
                'requires' => 'x86_64',
                'containers' => [
                    'php-react' => [
                        'image' => 'php-react',
                        'version' => 7.4,
                    ],
                ],
            ],
        ];
        $builder = $this->buildCompiler();

        $this->expectException(InvalidArgumentException::class);
        $builder->extends(
            $definitions,
        );
    }

    public function testCompileWithWrongExtendsInContainer(): void
    {
        $definitions = [
            'shell' => [
                'upgrade' => [
                    'strategy' => 'recreate',
                ],
                'requires' => 'x86_64',
                'containers' => [
                    'php-react' => [
                        'image' => 'php-react',
                        'extends' => new stdClass(),
                        'version' => 7.4,
                    ],
                ],
            ],
        ];
        $builder = $this->buildCompiler();

        $this->expectException(InvalidArgumentException::class);
        $builder->extends(
            $definitions,
        );
    }

    public function testCompileWithNonExistantExtends(): void
    {
        $definitions = [
            'shell' => [
                'extends' => 'other',
                'upgrade' => [
                    'strategy' => 'recreate',
                ],
                'requires' => 'x86_64',
                'containers' => [
                    'php-react' => [
                        'image' => 'php-react',
                        'version' => 7.4,
                    ],
                ],
            ],
        ];
        $builder = $this->buildCompiler();

        $this->expectException(DomainException::class);
        $builder->extends(
            $definitions,
        );
    }

    public function testCompileWithNonExistantExtendsInContainer(): void
    {
        $definitions = [
            'shell' => [
                'upgrade' => [
                    'strategy' => 'recreate',
                ],
                'requires' => 'x86_64',
                'containers' => [
                    'php-react' => [
                        'image' => 'php-react',
                        'extends' => 'other',
                        'version' => 7.4,
                    ],
                ],
            ],
        ];
        $builder = $this->buildCompiler();

        $this->expectException(DomainException::class);
        $builder->extends(
            $definitions,
        );
    }


    public function testCompileWithExtends(): void
    {
        $definitions = [
            'node-pod' => [
                'extends' => 'foo-ext',
                'upgrade' => [
                    'max-upgrading-pods' => 2,
                ],
                'containers' => [
                    'node-react' => [
                        'image' => 'node-react',
                        'version' => 123,
                        'listen' => [8181],
                    ],
                ],
            ],
            'shell' => [
                'replicas' => 1,
                'upgrade' => [
                    'strategy' => 'recreate',
                ],
                'requires' => 'x86_64',
                'containers' => [
                    'php-react' => [
                        'extends' => 'bar-ext',
                        'version' => 7.4,
                    ],
                ],
            ],
        ];
        $builder = $this->buildCompiler();

        $this->assertInstanceOf(PodCompiler::class, $builder->extends(
            $definitions,
        ));

        $this->assertEquals($definitions, [
            'node-pod' => [
                'extends' => 'foo-ext',
                'replicas' => 2,
                'oci-registry-config-name' => 'bar',
                'upgrade' => [
                    'max-upgrading-pods' => 2,
                    'max-unavailable-pods' => 1,
                ],
                'containers' => [
                    'node-react' => [
                        'image' => 'node-react',
                        'version' => 123,
                        'listen' => [8181],
                    ],
                ],
            ],
            'shell' => [
                'replicas' => 1,
                'upgrade' => [
                    'strategy' => 'recreate',
                ],
                'requires' => 'x86_64',
                'containers' => [
                    'php-react' => [
                        'extends' => 'bar-ext',
                        'image' => 'mongo-react',
                        'version' => 7.4,
                    ],
                ],
            ],
        ]);
    }
}
