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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Job;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(JobUnit::class)]
class   JobUnitTest extends TestCase
{
    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;
    
    /**
     * @var ImageRegistryInterface
     */
    private $imagesRegistry;

    /**
     * @var Cluster
     */
    private $cluster;

    /**
     * @return SourceRepositoryInterface
     */
    public function getSourceRepositoryMock(): SourceRepositoryInterface
    {
        if (!$this->sourceRepository instanceof SourceRepositoryInterface) {
            $this->sourceRepository = $this->createMock(SourceRepositoryInterface::class);
        }

        return $this->sourceRepository;
    }

    /**
     * @return ImageRegistryInterface
     */
    public function getImagesRegistryMock(): ImageRegistryInterface
    {
        if (!$this->imagesRegistry instanceof ImageRegistryInterface) {
            $this->imagesRegistry = $this->createMock(ImageRegistryInterface::class);

            $this->imagesRegistry->expects($this->any())->method('getApiUrl')->willReturn('foo');
            $this->imagesRegistry->expects($this->any())->method('getIdentity')->willReturn(
                $this->createMock(IdentityInterface::class)
            );
        }

        return $this->imagesRegistry;
    }

    /**
     * @return Cluster|MockObject
     */
    public function getClusterMock(): Cluster
    {
        if (!$this->cluster instanceof Cluster) {
            $this->cluster = $this->createMock(Cluster::class);
        }

        return $this->cluster;
    }

    private function buildObject(
        array $extra = [],
        ImageRegistryInterface $imageRegistry = null,
        string $id = 'test',
        string $prefix = 'bar',
        array $defaults = [],
        array $quotas = [],
        array $variables =  [
            'foo' => 'bar',
            'bar' => 'FOO',
        ],
    ) {
        return new JobUnit(
            id: $id,
            projectResume: ['@class' => Project::class,'id' => 'bar', 'name' => 'h€llo Ba$r'],
            environment: new Environment('foo'),
            prefix: $prefix,
            sourceRepository: $this->getSourceRepositoryMock(),
            imagesRegistry: $imageRegistry ?? $this->getImagesRegistryMock(),
            clusters: [$this->getClusterMock()],
            variables: $variables,
            history: new History(null, 'foo', new \DateTimeImmutable('2018-05-01')),
            extra: $extra,
            defaults: $defaults,
            quotas: $quotas,
        );
    }

    public function testGetShortId()
    {
        $obj = $this->buildObject();
        self::assertEquals(
            'test',
            $obj->getId(),
        );

        self::assertEquals(
            'test',
            $obj->getShortId(),
        );

        $obj = $this->buildObject(id: 'azertyuiopqsdfghjklm');
        self::assertEquals(
            'azertyuiopqsdfghjklm',
            $obj->getId(),
        );

        self::assertEquals(
            'azer-jklm',
            $obj->getShortId(),
        );
    }

    public function testGetEnvironmentTag()
    {
        self::assertIsString(
            $this->buildObject()->getEnvironmentTag()
        );
    }

    public function testGetProjectNormalizedName()
    {
        self::assertIsString(
            $this->buildObject()->getProjectNormalizedName()
        );
    }

    public function testConfigureCloningAgentOnSuccess()
    {
        $object = $this->buildObject();

        $agent = $this->createMock(CloningAgentInterface::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $agent->expects($this->once())
            ->method('configure')
            ->with($this->getSourceRepositoryMock(), $workspace);

        self::assertInstanceOf(
            JobUnit::class,
            $object->configureCloningAgent($agent, $workspace, $promise)
        );
    }

    public function testConfigureCloningAgentOnError()
    {
        $object = $this->buildObject();

        $agent = $this->createMock(CloningAgentInterface::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $agent->expects($this->once())
            ->method('configure')
            ->with($this->getSourceRepositoryMock(), $workspace)
            ->willThrowException(new \Exception());

        self::assertInstanceOf(
            JobUnit::class,
            $object->configureCloningAgent($agent, $workspace, $promise)
        );
    }

    public function testConfigureImageBuilderOnSuccess()
    {
        $object = $this->buildObject();

        $builder = $this->createMock(ImageBuilder::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $builder->expects($this->once())
            ->method('configure')
            ->with('bar', 'foo', self::callback(
                fn ($o) => $o instanceof IdentityInterface
            ));

        self::assertInstanceOf(
            JobUnit::class,
            $object->configureImageBuilder($builder, $promise)
        );
    }

    public function testConfigureImageBuilderOnError()
    {
        $object = $this->buildObject();

        $builder = $this->createMock(ImageBuilder::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $builder->expects($this->once())
            ->method('configure')
            ->with('bar', 'foo', self::callback(
                fn ($o) => $o instanceof IdentityInterface
            ))
            ->willThrowException(new \Exception());

        self::assertInstanceOf(
            JobUnit::class,
            $object->configureImageBuilder($builder, $promise)
        );
    }

    public function testConfigureClusterOnSuccess()
    {
        $object = $this->buildObject();

        $directory = $this->createMock(Directory::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $this->getClusterMock()
            ->expects($this->once())
            ->method('selectCluster')
            ->with($directory)
            ->willReturnCallback(
                function ($c, CompiledDeploymentInterface $cd, PromiseInterface $p) {
                    $p->success($this->createMock(ClusterClientInterface::class));

                    return $this->getClusterMock();
                }
            );

        self::assertInstanceOf(
            JobUnit::class,
            $object->configureCluster($directory, $promise, $this->createMock(CompiledDeploymentInterface::class))
        );
    }

    public function testConfigureClusterOnError()
    {
        $object = $this->buildObject();

        $directory = $this->createMock(Directory::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->getClusterMock()
            ->expects($this->once())
            ->method('selectCluster')
            ->with($directory)
            ->willThrowException(new \Exception());

        self::assertInstanceOf(
            JobUnit::class,
            $object->configureCluster($directory, $promise, $this->createMock(CompiledDeploymentInterface::class))
        );
    }

    public function testConfigureClusterOnErrorOnConfigure()
    {
        $object = $this->buildObject();

        $directory = $this->createMock(Directory::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->getClusterMock()
            ->expects($this->once())
            ->method('selectCluster')
            ->with($directory)
            ->willReturnCallback(
                function ($c, PromiseInterface $p) {
                    $p->fail(new \Exception());

                    return $this->getClusterMock();
                }
            );

        self::assertInstanceOf(
            JobUnit::class,
            $object->configureCluster($directory, $promise, $this->createMock(CompiledDeploymentInterface::class))
        );
    }

    public function testExportToMeDataBadNormalizer()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->exportToMeData(new \stdClass(), []);
    }

    public function testExportToMeDataBadContext()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->exportToMeData(
            $this->createMock(EastNormalizerInterface::class),
            new \stdClass()
        );
    }

    public function testExportToMe()
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
                'source_repository' => $this->getSourceRepositoryMock(),
                'images_repository' => $this->getImagesRegistryMock(),
                'clusters' => [$this->getClusterMock()],
                'variables' => ['foo' => 'bar', 'bar' => 'FOO'],
                'history' => new History(null, 'foo', new \DateTimeImmutable('2018-05-01')),
            ]);

        self::assertInstanceOf(
            JobUnit::class,
            $this->buildObject()->exportToMeData(
                $normalizer,
                ['foo' => 'bar']
            )
        );
    }

    public function testUpdateVariablesInWithDefaultsOciNotDefined()
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

        $identity = $this->createMock(IdentityWithConfigNameInterface::class);
        $identity->expects($this->any())
            ->method('getConfigName')
            ->willReturn('fooName');

        $imageRegistry = $this->createMock(ImageRegistryInterface::class);
        $imageRegistry->expects($this->any())->method('getApiUrl')->willReturn('foo');
        $imageRegistry->expects($this->any())->method('getIdentity')->willReturn($identity);

        self::assertInstanceOf(
            JobUnit::class,
            $this->buildObject(imageRegistry: $imageRegistry, prefix: '')->updateVariablesIn(
                $ori,
                new Promise(
                    function (array $result) {
                        self::assertEquals(
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

    public function testUpdateVariablesInWithDefaultsOciAlreadyDefined()
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

        $identity = $this->createMock(IdentityWithConfigNameInterface::class);
        $identity->expects($this->any())
            ->method('getConfigName')
            ->willReturn('fooName');

        $imageRegistry = $this->createMock(ImageRegistryInterface::class);
        $imageRegistry->expects($this->any())->method('getApiUrl')->willReturn('foo');
        $imageRegistry->expects($this->any())->method('getIdentity')->willReturn($identity);

        self::assertInstanceOf(
            JobUnit::class,
            $this->buildObject(imageRegistry: $imageRegistry)->updateVariablesIn(
                $ori,
                new Promise(
                    function (array $result) {
                        self::assertEquals(
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

    public function testUpdateVariablesInWithDefaultsOciConfigNameEmpty()
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

        $identity = $this->createMock(IdentityWithConfigNameInterface::class);
        $identity->expects($this->any())
            ->method('getConfigName')
            ->willReturn('');

        $imageRegistry = $this->createMock(ImageRegistryInterface::class);
        $imageRegistry->expects($this->any())->method('getApiUrl')->willReturn('foo');
        $imageRegistry->expects($this->any())->method('getIdentity')->willReturn($identity);

        self::assertInstanceOf(
            JobUnit::class,
            $this->buildObject(imageRegistry: $imageRegistry)->updateVariablesIn(
                $ori,
                new Promise(
                    function (array $result) {
                        self::assertEquals(
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

    public function testUpdateVariablesInWithDefaultsNotDefined()
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

        $identity = $this->createMock(IdentityWithConfigNameInterface::class);
        $identity->expects($this->any())
            ->method('getConfigName')
            ->willReturn('fooName');

        $imageRegistry = $this->createMock(ImageRegistryInterface::class);
        $imageRegistry->expects($this->any())->method('getApiUrl')->willReturn('foo');
        $imageRegistry->expects($this->any())->method('getIdentity')->willReturn($identity);

        self::assertInstanceOf(
            JobUnit::class,
            $this->buildObject(
                defaults: ['foo' => 'bar'],
                imageRegistry: $imageRegistry,
                prefix: ''
            )->updateVariablesIn(
                $ori,
                new Promise(
                    function (array $result) {
                        self::assertEquals(
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

    public function testUpdateVariablesInWithDefaultsAlreadyDefinedInJob()
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

        $identity = $this->createMock(IdentityWithConfigNameInterface::class);
        $identity->expects($this->any())
            ->method('getConfigName')
            ->willReturn('fooName');

        $imageRegistry = $this->createMock(ImageRegistryInterface::class);
        $imageRegistry->expects($this->any())->method('getApiUrl')->willReturn('foo');
        $imageRegistry->expects($this->any())->method('getIdentity')->willReturn($identity);

        self::assertInstanceOf(
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
                    function (array $result) {
                        self::assertEquals(
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

    public function testUpdateVariablesInWithDefaultsConfigNameEmpty()
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

        $identity = $this->createMock(IdentityWithConfigNameInterface::class);
        $identity->expects($this->any())
            ->method('getConfigName')
            ->willReturn('');

        $imageRegistry = $this->createMock(ImageRegistryInterface::class);
        $imageRegistry->expects($this->any())->method('getApiUrl')->willReturn('foo');
        $imageRegistry->expects($this->any())->method('getIdentity')->willReturn($identity);

        self::assertInstanceOf(
            JobUnit::class,
            $this->buildObject(defaults: ['foo' => 'bar'], imageRegistry: $imageRegistry)->updateVariablesIn(
                $ori,
                new Promise(
                    function (array $result) {
                        self::assertEquals(
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

    public function testUpdateVariablesInWithPrefix()
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

        self::assertInstanceOf(
            JobUnit::class,
            $this->buildObject(prefix: 'a-prefix')->updateVariablesIn(
                $ori,
                new Promise(
                    function (array $result) {
                        self::assertEquals(
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

    public function testUpdateVariablesInForEmpty()
    {
        $ori = [];

        self::assertInstanceOf(
            JobUnit::class,
            $this->buildObject()->updateVariablesIn(
                $ori,
                new Promise(
                    function (array $result) {
                        self::assertEquals(
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

    public function testUpdateVariablesInWithNoKeyFound()
    {
        $ori = [
            'foo' => 'foo',
            'bar' => [
                '${foo2}',
            ],
        ];

        self::assertInstanceOf(
            JobUnit::class,
            $this->buildObject()->updateVariablesIn(
                $ori,
                new Promise(
                    function (array $result) {
                        self::fail();
                    },
                    function (Throwable $error) {
                        self::assertInstanceOf(\DomainException::class, $error);
                    }
                )
            )
        );
    }

    public function testRunWithExtra()
    {
        self::assertInstanceOf(
            JobUnitInterface::class,
            $this->buildObject()->runWithExtra(function () {
                self::fail('Must not be called if no extra');
            })
        );

        $extra = [];
        self::assertInstanceOf(
            JobUnitInterface::class,
            $this->buildObject(['foo' => 'bar'])->runWithExtra(function ($e) use (&$extra) {
                $extra = $e;
            })
        );

        self::assertEquals(
            ['foo' => 'bar'],
            $extra
        );
    }

    public function testPrepareQuotasWithoutQuotas()
    {
        $factory = $this->createMock(QuotaFactory::class);
        $factory->expects($this->never())
            ->method('create');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->with([]);

        self::assertInstanceOf(
            JobUnitInterface::class,
            $this->buildObject()->prepareQuotas($factory, $promise)
        );
    }

    public function testPrepareQuotasWithQuotas()
    {
        $factory = $this->createMock(QuotaFactory::class);
        $factory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($availabilitty = $this->createMock(AvailabilityInterface::class));

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->with(
                [
                    'cpu' => $availabilitty,
                    'memory' => $availabilitty,
                ]
            );

        self::assertInstanceOf(
            JobUnitInterface::class,
            $this->buildObject(
                quotas: [
                    new AccountQuota('compute', 'cpu', '4'),
                    new AccountQuota('memory', 'memory', '4'),
                ]
            )->prepareQuotas($factory, $promise)
        );
    }

    public function testFilteringConditionsWithoutAnyCondition()
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

        $promise->expects($this->any())
            ->method('fail')
            ->willReturnCallback(fn (Throwable $e) => throw $e);

        $this->buildObject()->filteringConditions($values, $promise);
    }

    public function testFilteringConditionsWithErrorOnIs()
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

    public function testFilteringConditionsWithErrorIsNot()
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

    public function testFilteringConditionsWithAllConditions()
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

        $promise->expects($this->any())
            ->method('fail')
            ->willReturnCallback(fn (Throwable $e) => throw $e);

        $this->buildObject(variables: [
            'ENV' => 'prod',
            'STAGE' => 'on',
            'VAL_A' => 3,
        ])->filteringConditions($values, $promise);
    }

    public function testFilteringConditionsWithNestedCondition()
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

        $promise->expects($this->any())
            ->method('fail')
            ->willReturnCallback(fn (Throwable $e) => throw $e);

        $this->buildObject(variables: ['ENV' => 'prod'])->filteringConditions($values, $promise);
    }
}
