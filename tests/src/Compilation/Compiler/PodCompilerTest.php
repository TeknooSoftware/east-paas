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
use Teknoo\East\Paas\Compilation\Compiler\ResourceManager;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\VolumeInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Compilation\Compiler\PodCompiler;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Compilation\Compiler\PodCompiler
 */
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

    public function testCompileWithoutDefinitions()
    {
        $definitions = [];

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::never())->method('addPod');

        self::assertInstanceOf(
            PodCompiler::class,
            $this->buildCompiler()->compile(
                $definitions,
                $compiledDeployment,
                $this->createMock(JobWorkspaceInterface::class),
                $this->createMock(JobUnitInterface::class),
                $this->createMock(ResourceManager::class),
            )
        );
    }

    public function testCompile()
    {
        $definitions = $this->getDefinitionsArray();
        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::exactly(3))->method('addPod');
        $compiledDeployment->expects(self::exactly(1))
            ->method('importVolume')
            ->willReturnCallback(
                function (
                    string $volumeFrom,
                    string $mountPath,
                    PromiseInterface $promise,
                ) use ($compiledDeployment) {
                    $promise->success($this->createMock(VolumeInterface::class));

                    return $compiledDeployment;
                }
            );

        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class);

        self::assertInstanceOf(
            PodCompiler::class,
            $builder->compile(
                $definitions,
                $compiledDeployment,
                $workspace,
                $jobUnit,
                $this->createMock(ResourceManager::class),
                'fooBar',
                'fooBar',
            )
        );
    }

    public function testCompileWithInvalidVolume()
    {
        $definitions = $this->getDefinitionsArray();
        unset($definitions['node-pod']);
        unset($definitions['shell']);
        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::never())->method('addPod');
        $compiledDeployment->expects(self::any())
            ->method('importVolume')
            ->willReturnCallback(
                function (string $volumeFrom, string $mountPath, PromiseInterface $promise) use ($compiledDeployment) {
                    $promise->fail(new DomainException('foo'));

                    return $compiledDeployment;
                }
            );

        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class);

        $this->expectException(DomainException::class);

        self::assertInstanceOf(
            PodCompiler::class,
            $builder->compile(
                $definitions,
                $compiledDeployment,
                $workspace,
                $jobUnit,
                $this->createMock(ResourceManager::class),
                'fooBar',
                'fooBar',
            )
        );
    }

    public function testCompileWithoutStorageIdentifierInVolume()
    {
        $definitions = $this->getDefinitionsArray();
        unset($definitions['node-pod']);
        unset($definitions['shell']);
        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::never())->method('addPod');
        $compiledDeployment->expects(self::any())
            ->method('importVolume')
            ->willReturnCallback(
                function (string $volumeFrom, string $mountPath, PromiseInterface $promise) use ($compiledDeployment) {
                    $promise->fail(new DomainException('foo'));

                    return $compiledDeployment;
                }
            );

        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class);

        $this->expectException(\RuntimeException::class);

        self::assertInstanceOf(
            PodCompiler::class,
            $builder->compile(
                $definitions,
                $compiledDeployment,
                $workspace,
                $jobUnit,
                $this->createMock(ResourceManager::class),
                null,
                'fooBar',
            )
        );
    }

    public function testCompileWithoutStorageSizeInVolume()
    {
        $definitions = $this->getDefinitionsArray();
        unset($definitions['node-pod']);
        unset($definitions['shell']);
        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::never())->method('addPod');
        $compiledDeployment->expects(self::any())
            ->method('importVolume')
            ->willReturnCallback(
                function (string $volumeFrom, string $mountPath, PromiseInterface $promise) use ($compiledDeployment) {
                    $promise->fail(new DomainException('foo'));

                    return $compiledDeployment;
                }
            );

        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class);

        $this->expectException(\RuntimeException::class);

        self::assertInstanceOf(
            PodCompiler::class,
            $builder->compile(
                $definitions,
                $compiledDeployment,
                $workspace,
                $jobUnit,
                $this->createMock(ResourceManager::class),
                'fooBar',
            )
        );
    }

    public function testCompileDeploymentWithVolumeWithoutMountPath()
    {
        $this->expectException(\RuntimeException::class);

        $definitions = $this->getDefinitionsArray();
        unset($definitions['php-pod']['containers']['php-composer']['volumes']['embedded']['mount-path']);
        unset($definitions['php-pod']['containers']['php-composer']['volumes']['persistent_volume']['mount-path']);
        unset($definitions['php-pod']['containers']['php-composer']['volumes']['other_name2']['mount-path']);
        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::any())->method('addPod');

        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class);

        self::assertInstanceOf(
            PodCompiler::class,
            $builder->compile(
                $definitions,
                $compiledDeployment,
                $workspace,
                $jobUnit,
                $this->createMock(ResourceManager::class),
                'fooBar',
                'fooBar',
            )
        );
    }

    public function testCompileWithWrongExtends()
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

    public function testCompileWithWrongExtendsInContainer()
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

    public function testCompileWithNonExistantExtends()
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

    public function testCompileWithNonExistantExtendsInContainer()
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


    public function testCompileWithExtends()
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

        self::assertInstanceOf(
            PodCompiler::class,
            $builder->extends(
                $definitions,
            )
        );

        self::assertEquals(
            $definitions,
            [
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
            ]
        );
    }
}
