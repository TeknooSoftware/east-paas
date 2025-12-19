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

namespace Teknoo\Tests\East\Paas\Job;

use DateTimeImmutable;
use DomainException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use stdClass;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Compilation\Compiler\Quota\Factory as QuotaFactory;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface as ClusterClientInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuilderInterface as ImageBuilder;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\Quota\AvailabilityInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Paas\Contracts\Object\IdentityWithConfigNameInterface;
use Teknoo\East\Paas\Contracts\Object\ImageRegistryInterface;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Job\JobUnit;
use Teknoo\East\Paas\Object\AccountQuota;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;
use TypeError;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(JobUnit::class)]
class JobUnitTest extends TestCase
{
    private (SourceRepositoryInterface&MockObject)|(SourceRepositoryInterface&Stub)|null $sourceRepository = null;

    private (ImageRegistryInterface&MockObject)|(ImageRegistryInterface&Stub)|null $imagesRegistry = null;

    private (Cluster&MockObject)|(Cluster&Stub)|null $cluster = null;

    public function getSourceRepositoryMock(bool $stub = false): (SourceRepositoryInterface&Stub)|(SourceRepositoryInterface&MockObject)
    {
        if (!$this->sourceRepository instanceof SourceRepositoryInterface) {
            if ($stub) {
                $this->sourceRepository = $this->createStub(SourceRepositoryInterface::class);
            } else {
                $this->sourceRepository = $this->createMock(SourceRepositoryInterface::class);
            }
        }

        return $this->sourceRepository;
    }

    public function getImagesRegistryMock(bool $stub = false): (ImageRegistryInterface&MockObject)|(ImageRegistryInterface&Stub)
    {
        if (!$this->imagesRegistry instanceof ImageRegistryInterface) {
            if ($stub) {
                $this->imagesRegistry = $this->createStub(ImageRegistryInterface::class);
            } else {
                $this->imagesRegistry = $this->createMock(ImageRegistryInterface::class);
            }

            $this->imagesRegistry->method('getApiUrl')->willReturn('foo');
            $this->imagesRegistry->method('getIdentity')->willReturn(
                $this->createStub(IdentityInterface::class)
            );
        }

        return $this->imagesRegistry;
    }


    public function getClusterMock(bool $stub = false): (Cluster&Stub)|(Cluster&MockObject)
    {
        if (!$this->cluster instanceof Cluster) {
            if ($stub) {
                $this->cluster = $this->createStub(Cluster::class);
            } else {
                $this->cluster = $this->createMock(Cluster::class);
            }
        }

        return $this->cluster;
    }

    private function buildObject(
        array $extra = [],
        ?ImageRegistryInterface $imageRegistry = null,
        string $id = 'test',
        string $prefix = 'bar',
        array $defaults = [],
        array $quotas = [],
        array $variables =  [
            'foo' => 'bar',
            'bar' => 'FOO',
        ],
    ): JobUnit {
        return new JobUnit(
            id: $id,
            projectResume: ['@class' => Project::class,'id' => 'bar', 'name' => 'h€llo Ba$r'],
            environment: new Environment('foo'),
            prefix: $prefix,
            sourceRepository: $this->getSourceRepositoryMock(true),
            imagesRegistry: $imageRegistry ?? $this->getImagesRegistryMock(true),
            clusters: [$this->getClusterMock(true)],
            variables: $variables,
            history: new History(null, 'foo', new DateTimeImmutable('2018-05-01')),
            extra: $extra,
            defaults: $defaults,
            quotas: $quotas,
        );
    }

    public function testGetShortId(): void
    {
        $obj = $this->buildObject();
        $this->assertEquals(
            'test',
            $obj->getId(),
        );

        $this->assertEquals(
            'test',
            $obj->getShortId(),
        );

        $obj = $this->buildObject(id: 'azertyuiopqsdfghjklm');
        $this->assertEquals(
            'azertyuiopqsdfghjklm',
            $obj->getId(),
        );

        $this->assertEquals(
            'azer-jklm',
            $obj->getShortId(),
        );
    }

    public function testGetEnvironmentTag(): void
    {
        $this->assertIsString(
            $this->buildObject()->getEnvironmentTag()
        );
    }

    public function testGetProjectNormalizedName(): void
    {
        $this->assertIsString(
            $this->buildObject()->getProjectNormalizedName()
        );
    }

    public function testConfigureCloningAgentOnSuccess(): void
    {
        $object = $this->buildObject();

        $agent = $this->createMock(CloningAgentInterface::class);
        $workspace = $this->createStub(JobWorkspaceInterface::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $agent->expects($this->once())
            ->method('configure')
            ->with($this->getSourceRepositoryMock(), $workspace);

        $this->assertInstanceOf(
            JobUnit::class,
            $object->configureCloningAgent($agent, $workspace, $promise)
        );
    }

    public function testConfigureCloningAgentOnError(): void
    {
        $object = $this->buildObject();

        $agent = $this->createMock(CloningAgentInterface::class);
        $workspace = $this->createStub(JobWorkspaceInterface::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $agent->expects($this->once())
            ->method('configure')
            ->with($this->getSourceRepositoryMock(), $workspace)
            ->willThrowException(new Exception());

        $this->assertInstanceOf(
            JobUnit::class,
            $object->configureCloningAgent($agent, $workspace, $promise)
        );
    }

    public function testConfigureImageBuilderOnSuccess(): void
    {
        $object = $this->buildObject();

        $builder = $this->createMock(ImageBuilder::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $builder->expects($this->once())
            ->method('configure')
            ->with('bar', 'foo', self::callback(
                fn ($o): bool => $o instanceof IdentityInterface
            ));

        $this->assertInstanceOf(
            JobUnit::class,
            $object->configureImageBuilder($builder, $promise)
        );
    }

    public function testConfigureImageBuilderOnError(): void
    {
        $object = $this->buildObject();

        $builder = $this->createMock(ImageBuilder::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $builder->expects($this->once())
            ->method('configure')
            ->with('bar', 'foo', self::callback(
                fn ($o): bool => $o instanceof IdentityInterface
            ))
            ->willThrowException(new Exception());

        $this->assertInstanceOf(
            JobUnit::class,
            $object->configureImageBuilder($builder, $promise)
        );
    }

    public function testConfigureClusterOnSuccess(): void
    {
        $directory = $this->createStub(Directory::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $this->getClusterMock()
            ->expects($this->once())
            ->method('selectCluster')
            ->with($directory)
            ->willReturnCallback(
                function ($c, CompiledDeploymentInterface $cd, PromiseInterface $p): (Cluster&MockObject)|(Cluster&Stub) {
                    $p->success($this->createStub(ClusterClientInterface::class));

                    return $this->getClusterMock();
                }
            );

        $object = $this->buildObject();
        $this->assertInstanceOf(
            JobUnit::class,
            $object->configureCluster($directory, $promise, $this->createStub(CompiledDeploymentInterface::class))
        );
    }

    public function testConfigureClusterOnError(): void
    {
        $directory = $this->createStub(Directory::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->getClusterMock()
            ->expects($this->once())
            ->method('selectCluster')
            ->with($directory)
            ->willThrowException(new Exception());

        $object = $this->buildObject();
        $this->assertInstanceOf(
            JobUnit::class,
            $object->configureCluster($directory, $promise, $this->createStub(CompiledDeploymentInterface::class))
        );
    }

    public function testConfigureClusterOnErrorOnConfigure(): void
    {
        $directory = $this->createStub(Directory::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->getClusterMock()
            ->expects($this->once())
            ->method('selectCluster')
            ->with($directory)
            ->willReturnCallback(
                function ($c, PromiseInterface $p): (Cluster&MockObject)|(Cluster&Stub) {
                    $p->fail(new Exception());

                    return $this->getClusterMock();
                }
            );

        $object = $this->buildObject();
        $this->assertInstanceOf(
            JobUnit::class,
            $object->configureCluster($directory, $promise, $this->createStub(CompiledDeploymentInterface::class))
        );
    }

    public function testExportToMeDataBadNormalizer(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->exportToMeData(new stdClass(), []);
    }

    public function testExportToMeDataBadContext(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->exportToMeData(
            $this->createStub(EastNormalizerInterface::class),
            new stdClass()
        );
    }

    public function testExportToMe(): void
    {
        $normalizer = $this->createMock(EastNormalizerInterface::class);
        $normalizer->expects($this->once())
            ->method('injectData')
            ->with([
                '@class' => Job::class,
                'id' => 'test',
                'project' => ['@class' => Project::class,'id' => 'bar', 'name' => 'h€llo Ba$r'],
                'environment' => new Environment('foo'),
                'prefix' => 'bar',
                'source_repository' => $this->getSourceRepositoryMock(true),
                'images_repository' => $this->getImagesRegistryMock(true),
                'clusters' => [$this->getClusterMock(true)],
                'variables' => ['foo' => 'bar', 'bar' => 'FOO'],
                'history' => new History(null, 'foo', new DateTimeImmutable('2018-05-01')),
            ]);

        $this->assertInstanceOf(
            JobUnit::class,
            $this->buildObject()->exportToMeData(
                $normalizer,
                ['foo' => 'bar']
            )
        );
    }

    public function testUpdateVariablesInWithDefaultsOciNotDefined(): void
    {
        $ori = [
            'foo' => 'foo',
            'bar' => [
                '${foo}',
                '${foo} text ${bar}',
            ],
            'test-prefix' => 'R{value}/bar',
            'bar-test' => 'will-be-removed',
            '${foo}-test' => 'text',
            '${foo}-array' => [
                '${foo}-text' => 'bar',
            ],
        ];

        $identity = $this->createStub(IdentityWithConfigNameInterface::class);
        $identity
            ->method('getConfigName')
            ->willReturn('fooName');

        $imageRegistry = $this->createStub(ImageRegistryInterface::class);
        $imageRegistry->method('getApiUrl')->willReturn('foo');
        $imageRegistry->method('getIdentity')->willReturn($identity);

        $this->assertInstanceOf(
            JobUnit::class,
            $this->buildObject(imageRegistry: $imageRegistry, prefix: '')->updateVariablesIn(
                $ori,
                new Promise(
                    function (array $result): void {
                        $this->assertEquals(
                            [
                                'foo' => 'foo',
                                'bar' => [
                                    'bar',
                                    'bar text FOO',
                                ],
                                'test-prefix' => 'value/bar',
                                'bar-test' => 'text',
                                'bar-array' => [
                                    'bar-text' => 'bar',
                                ],
                                'paas' => [
                                    'prefix' => '',
                                ],
                                'defaults' => [
                                    'oci-registry-config-name' => 'fooName',
                                ],
                            ],
                            $result
                        );
                    },
                    function (Throwable  $error): never {
                        throw $error;
                    }
                )
            )
        );
    }

    public function testUpdateVariablesInWithDefaultsOciAlreadyDefined(): void
    {
        $ori = [
            'foo' => 'foo',
            'bar' => [
                '${foo}',
                '${foo} text ${bar}',
            ],
            'bar-test' => 'will-be-removed',
            '${foo}-test' => 'text',
            '${foo}-array' => [
                '${foo}-text' => 'bar',
            ],
            'defaults' => [
                'oci-registry-config-name' => 'barName',
            ],
        ];

        $identity = $this->createStub(IdentityWithConfigNameInterface::class);
        $identity
            ->method('getConfigName')
            ->willReturn('fooName');

        $imageRegistry = $this->createStub(ImageRegistryInterface::class);
        $imageRegistry->method('getApiUrl')->willReturn('foo');
        $imageRegistry->method('getIdentity')->willReturn($identity);

        $this->assertInstanceOf(
            JobUnit::class,
            $this->buildObject(imageRegistry: $imageRegistry)->updateVariablesIn(
                $ori,
                new Promise(
                    function (array $result): void {
                        $this->assertEquals(
                            [
                                'foo' => 'foo',
                                'bar' => [
                                    'bar',
                                    'bar text FOO',
                                ],
                                'bar-test' => 'text',
                                'bar-array' => [
                                    'bar-text' => 'bar',
                                ],
                                'paas' => [
                                    'prefix' => 'bar',
                                ],
                                'defaults' => [
                                    'oci-registry-config-name' => 'barName',
                                ],
                            ],
                            $result
                        );
                    },
                    function (Throwable  $error): never {
                        throw $error;
                    }
                )
            )
        );
    }

    public function testUpdateVariablesInWithDefaultsOciConfigNameEmpty(): void
    {
        $ori = [
            'foo' => 'foo',
            'bar' => [
                '${foo}',
                '${foo} text ${bar}',
            ],
            'bar-test' => 'will-be-removed',
            '${foo}-test' => 'text',
            '${foo}-array' => [
                '${foo}-text' => 'bar',
            ],
        ];

        $identity = $this->createStub(IdentityWithConfigNameInterface::class);
        $identity
            ->method('getConfigName')
            ->willReturn('');

        $imageRegistry = $this->createStub(ImageRegistryInterface::class);
        $imageRegistry->method('getApiUrl')->willReturn('foo');
        $imageRegistry->method('getIdentity')->willReturn($identity);

        $this->assertInstanceOf(
            JobUnit::class,
            $this->buildObject(imageRegistry: $imageRegistry)->updateVariablesIn(
                $ori,
                new Promise(
                    function (array $result): void {
                        $this->assertEquals(
                            [
                                'foo' => 'foo',
                                'bar' => [
                                    'bar',
                                    'bar text FOO',
                                ],
                                'bar-test' => 'text',
                                'bar-array' => [
                                    'bar-text' => 'bar',
                                ],
                                'paas' => [
                                    'prefix' => 'bar',
                                ],
                                'defaults' => [],
                            ],
                            $result
                        );
                    },
                    function (Throwable  $error): never {
                        throw $error;
                    }
                )
            )
        );
    }

    public function testUpdateVariablesInWithDefaultsNotDefined(): void
    {
        $ori = [
            'foo' => 'foo',
            'bar' => [
                '${foo}',
                '${foo} text ${bar}',
            ],
            'test-prefix' => 'R{value}/bar',
            'bar-test' => 'will-be-removed',
            '${foo}-test' => 'text',
            '${foo}-array' => [
                '${foo}-text' => 'bar',
            ],
        ];

        $identity = $this->createStub(IdentityWithConfigNameInterface::class);
        $identity
            ->method('getConfigName')
            ->willReturn('fooName');

        $imageRegistry = $this->createStub(ImageRegistryInterface::class);
        $imageRegistry->method('getApiUrl')->willReturn('foo');
        $imageRegistry->method('getIdentity')->willReturn($identity);

        $this->assertInstanceOf(
            JobUnit::class,
            $this->buildObject(
                defaults: ['foo' => 'bar'],
                imageRegistry: $imageRegistry,
                prefix: ''
            )->updateVariablesIn(
                $ori,
                new Promise(
                    function (array $result): void {
                        $this->assertEquals(
                            [
                                'foo' => 'foo',
                                'bar' => [
                                    'bar',
                                    'bar text FOO',
                                ],
                                'test-prefix' => 'value/bar',
                                'bar-test' => 'text',
                                'bar-array' => [
                                    'bar-text' => 'bar',
                                ],
                                'paas' => [
                                    'prefix' => '',
                                ],
                                'defaults' => [
                                    'foo' => 'bar',
                                    'oci-registry-config-name' => 'fooName',
                                ],
                            ],
                            $result
                        );
                    },
                    function (Throwable  $error): never {
                        throw $error;
                    }
                )
            )
        );
    }

    public function testUpdateVariablesInWithDefaultsAlreadyDefinedInJob(): void
    {
        $ori = [
            'defaults' => [
                'clusters' => [
                    'cluster-one' => [
                        'storage-size' => 'bar'
                    ],
                ],
            ],
            'foo' => 'foo',
            'bar' => [
                '${foo}',
                '${foo} text ${bar}',
            ],
            'bar-test' => 'will-be-removed',
            '${foo}-test' => 'text',
            '${foo}-array' => [
                '${foo}-text' => 'bar',
            ],
        ];

        $identity = $this->createStub(IdentityWithConfigNameInterface::class);
        $identity
            ->method('getConfigName')
            ->willReturn('fooName');

        $imageRegistry = $this->createStub(ImageRegistryInterface::class);
        $imageRegistry->method('getApiUrl')->willReturn('foo');
        $imageRegistry->method('getIdentity')->willReturn($identity);

        $this->assertInstanceOf(
            JobUnit::class,
            $this->buildObject(
                defaults: [
                    'oci-registry-config-name' => 'barName',
                    'clusters' => [
                        'cluster-one' => [
                            'storage-provider' => 'foo'
                        ],
                    ],
                ],
                imageRegistry: $imageRegistry
            )->updateVariablesIn(
                $ori,
                new Promise(
                    function (array $result): void {
                        $this->assertEquals(
                            [
                                'foo' => 'foo',
                                'bar' => [
                                    'bar',
                                    'bar text FOO',
                                ],
                                'bar-test' => 'text',
                                'bar-array' => [
                                    'bar-text' => 'bar',
                                ],
                                'paas' => [
                                    'prefix' => 'bar',
                                ],
                                'defaults' => [
                                    'oci-registry-config-name' => 'barName',
                                    'clusters' => [
                                        'cluster-one' => [
                                            'storage-provider' => 'foo',
                                            'storage-size' => 'bar',
                                        ],
                                    ],
                                ],
                            ],
                            $result
                        );
                    },
                    function (Throwable  $error): never {
                        throw $error;
                    }
                )
            )
        );
    }

    public function testUpdateVariablesInWithDefaultsConfigNameEmpty(): void
    {
        $ori = [
            'foo' => 'foo',
            'bar' => [
                '${foo}',
                '${foo} text ${bar}',
            ],
            'bar-test' => 'will-be-removed',
            '${foo}-test' => 'text',
            '${foo}-array' => [
                '${foo}-text' => 'bar',
            ],
        ];

        $identity = $this->createStub(IdentityWithConfigNameInterface::class);
        $identity
            ->method('getConfigName')
            ->willReturn('');

        $imageRegistry = $this->createStub(ImageRegistryInterface::class);
        $imageRegistry->method('getApiUrl')->willReturn('foo');
        $imageRegistry->method('getIdentity')->willReturn($identity);

        $this->assertInstanceOf(
            JobUnit::class,
            $this->buildObject(defaults: ['foo' => 'bar'], imageRegistry: $imageRegistry)->updateVariablesIn(
                $ori,
                new Promise(
                    function (array $result): void {
                        $this->assertEquals(
                            [
                                'foo' => 'foo',
                                'bar' => [
                                    'bar',
                                    'bar text FOO',
                                ],
                                'bar-test' => 'text',
                                'bar-array' => [
                                    'bar-text' => 'bar',
                                ],
                                'paas' => [
                                    'prefix' => 'bar',
                                ],
                                'defaults' => ['foo' => 'bar'],
                            ],
                            $result
                        );
                    },
                    function (Throwable  $error): never {
                        throw $error;
                    }
                )
            )
        );
    }

    public function testUpdateVariablesInWithPrefix(): void
    {
        $ori = [
            'foo' => 'foo',
            'bar' => [
                '${foo}',
                '${foo} text ${bar}',
            ],
            'test-prefix' => 'R{value}/bar',
            'bar-test' => 'will-be-removed',
            '${foo}-test' => 'text',
            '${foo}-array' => [
                '${foo}-text' => 'bar',
            ],
        ];

        $this->assertInstanceOf(
            JobUnit::class,
            $this->buildObject(prefix: 'a-prefix')->updateVariablesIn(
                $ori,
                new Promise(
                    function (array $result): void {
                        $this->assertEquals(
                            [
                                'foo' => 'foo',
                                'bar' => [
                                    'bar',
                                    'bar text FOO',
                                ],
                                'test-prefix' => 'a-prefix-value/bar',
                                'bar-test' => 'text',
                                'bar-array' => [
                                    'bar-text' => 'bar',
                                ],
                                'paas' => [
                                    'prefix' => 'a-prefix',
                                ],
                                'defaults' => [],
                            ],
                            $result
                        );
                    },
                    function (Throwable  $error): never {
                        throw $error;
                    }
                )
            )
        );
    }

    public function testUpdateVariablesInForEmpty(): void
    {
        $ori = [];

        $this->assertInstanceOf(
            JobUnit::class,
            $this->buildObject()->updateVariablesIn(
                $ori,
                new Promise(
                    function (array $result): void {
                        $this->assertEquals(
                            [
                                'paas' => [
                                    'prefix' => 'bar',
                                ],
                                'defaults' => [],
                            ],
                            $result
                        );
                    },
                    function (Throwable  $error): never {
                        throw $error;
                    }
                )
            )
        );
    }

    public function testUpdateVariablesInWithNoKeyFound(): void
    {
        $ori = [
            'foo' => 'foo',
            'bar' => [
                '${foo2}',
            ],
        ];

        $this->assertInstanceOf(
            JobUnit::class,
            $this->buildObject()->updateVariablesIn(
                $ori,
                new Promise(
                    function (array $result): void {
                        self::fail();
                    },
                    function (Throwable $error): void {
                        $this->assertInstanceOf(DomainException::class, $error);
                    }
                )
            )
        );
    }

    public function testRunWithExtra(): void
    {
        $this->assertInstanceOf(
            JobUnitInterface::class,
            $this->buildObject()->runWithExtra(function (): void {
                self::fail('Must not be called if no extra');
            })
        );

        $extra = [];
        $this->assertInstanceOf(
            JobUnitInterface::class,
            $this->buildObject(['foo' => 'bar'])->runWithExtra(function ($e) use (&$extra): void {
                $extra = $e;
            })
        );

        $this->assertEquals(
            ['foo' => 'bar'],
            $extra
        );
    }

    public function testPrepareQuotasWithoutQuotas(): void
    {
        $factory = $this->createMock(QuotaFactory::class);
        $factory->expects($this->never())
            ->method('create');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->with([]);

        $this->assertInstanceOf(
            JobUnitInterface::class,
            $this->buildObject()->prepareQuotas($factory, $promise)
        );
    }

    public function testPrepareQuotasWithQuotas(): void
    {
        $factory = $this->createMock(QuotaFactory::class);
        $factory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($availabilitty = $this->createStub(AvailabilityInterface::class));

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->with(
                [
                    'cpu' => $availabilitty,
                    'memory' => $availabilitty,
                ]
            );

        $this->assertInstanceOf(
            JobUnitInterface::class,
            $this->buildObject(
                quotas: [
                    new AccountQuota('compute', 'cpu', '4'),
                    new AccountQuota('memory', 'memory', '4'),
                ]
            )->prepareQuotas($factory, $promise)
        );
    }

    public function testFilteringConditionsWithoutAnyCondition(): void
    {
        $values = [
            'paas' => [
                'version' => 1,
                'foo' => 'bar',
            ],
            'pods' => [
                'pod-a' => [
                    'replicas' => '2',
                    'containers' => [
                        'container-a' => [
                            'image' => 'foo',
                        ],
                        'container-b' => [
                            'image' => 'bar',
                        ],
                    ],
                ],
            ],
        ];

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->with($values);

        $promise
            ->method('fail')
            ->willReturnCallback(fn (Throwable $e): PromiseInterface => throw $e);

        $this->buildObject()->filteringConditions($values, $promise);
    }

    public function testFilteringConditionsWithErrorOnIs(): void
    {
        $values = [
            'paas' => [
                'version' => 1,
                'foo' => 'bar',
            ],
            'if{ENV is foo}' => [
                'paas' => [
                    'version' => 3,
                ]
            ],
        ];

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())
            ->method('success')
            ->with($values);

        $promise->expects($this->once())
            ->method('fail')
            ->willReturnCallback(fn (Throwable $e) => throw $e);

        $this->expectExceptionMessage('Criteria `foo` is not supported for `is` condition');

        $this->buildObject()->filteringConditions($values, $promise);
    }

    public function testFilteringConditionsWithErrorIsNot(): void
    {
        $values = [
            'paas' => [
                'version' => 1,
                'foo' => 'bar',
            ],
            'if{ENV isnot foo}' => [
                'paas' => [
                    'version' => 3,
                ]
            ],
        ];

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())
            ->method('success')
            ->with($values);

        $promise->expects($this->once())
            ->method('fail')
            ->willReturnCallback(fn (Throwable $e) => throw $e);

        $this->expectExceptionMessage('Criteria `foo` is not supported for `isnot` condition');

        $this->buildObject()->filteringConditions($values, $promise);
    }

    public function testFilteringConditionsWithAllConditions(): void
    {
        $values = [
            'paas' => [
                'version' => 1,
            ],
            'if{ENV isnot empty}' => [
                'paas' => [
                    'version' => 2,
                ],
            ],
            'if{ENV isnot null}' => [
                'bar' => 'foo',
            ],
            'if{ENV is null}' => [
                'hello' => 'world',
            ],
            'if{STAGE is empty}' => [
                'paas' => [
                    'version' => 3,
                ],
            ],
            'if{VAL_A<=3}' => [
                'paas' => [
                    'version' => 5,
                ]
            ],
            'if{VAL_A<3}' => [
                'paas' => [
                    'foo' => 'bar',
                ]
            ],
            'if{VAL_A>3}' => [
                'paas' => [
                    'version' => 4,
                ]
            ],
            'if{VAL_A>=3}' => [
                'paas' => [
                    'foo2' => 'bar2',
                ]
            ],
        ];

        $expected = [
            'paas' => [
                'version' => 5,
                'foo2' => 'bar2',
            ],
            'bar' => 'foo',
        ];

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->with($expected);

        $promise
            ->method('fail')
            ->willReturnCallback(fn (Throwable $e): PromiseInterface => throw $e);

        $this->buildObject(variables: [
            'ENV' => 'prod',
            'STAGE' => 'on',
            'VAL_A' => 3,
        ])->filteringConditions($values, $promise);
    }

    public function testFilteringConditionsWithNestedCondition(): void
    {
        $values = [
            'paas' => [
                'version' => 1,
                'foo' => 'bar',
            ],
            'if{ENV=prod}' => [
                'paas' => [
                    'version' => 2,
                ],
                'ingresses' => [
                    'foo' => [
                        'host' => 'bar',
                    ]
                ]
            ],
            'if{ENV=dev}' => [
                'paas' => [
                    'version' => 3,
                ]
            ],
            'pods' => [
                'pod-a' => [
                    'replicas' => '2',
                    'containers' => [
                        'container-a' => [
                            'image' => 'foo',
                        ],
                        'container-b' => [
                            'image' => 'bar',
                        ],
                    ],
                ],
                'if{ENV!=dev}' => [
                    'pod-a' => [
                        'replicas' => '3',
                    ]
                ]
            ],
        ];

        $expected = [
            'paas' => [
                'version' => 2,
                'foo' => 'bar',
            ],
            'ingresses' => [
                'foo' => [
                    'host' => 'bar',
                ]
            ],
            'pods' => [
                'pod-a' => [
                    'replicas' => '3',
                    'containers' => [
                        'container-a' => [
                            'image' => 'foo',
                        ],
                        'container-b' => [
                            'image' => 'bar',
                        ],
                    ],
                ],
            ],
        ];

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->with($expected);

        $promise
            ->method('fail')
            ->willReturnCallback(fn (Throwable $e): PromiseInterface => throw $e);

        $this->buildObject(variables: ['ENV' => 'prod'])->filteringConditions($values, $promise);
    }
}
