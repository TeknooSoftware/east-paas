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

namespace Teknoo\Tests\East\Paas\Job;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface as ClusterClientInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuilderInterface as ImageBuilder;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Job\JobUnit;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Paas\Contracts\Object\ImageRegistryInterface;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Job\JobUnit
 */
class JobUnitTest extends TestCase
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

            $this->imagesRegistry->expects(self::any())->method('getApiUrl')->willReturn('foo');
            $this->imagesRegistry->expects(self::any())->method('getIdentity')->willReturn(
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

    private function buildObject(?string $namespace = 'foo', array $extra = [])
    {
        return (new JobUnit(
            'test',
            ['@class' => Project::class,'id' => 'bar', 'name' => 'hello'],
            new Environment('foo'),
            $namespace,
            $this->getSourceRepositoryMock(),
            $this->getImagesRegistryMock(),
            [$this->getClusterMock()],
            [
                'foo' => 'bar',
                'bar' => 'FOO',
            ],
            new History(null, 'foo', new \DateTimeImmutable('2018-05-01')),
            $extra
        ));
    }

    public function testConfigureCloningAgentOnSuccess()
    {
        $object = $this->buildObject();

        $agent = $this->createMock(CloningAgentInterface::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success');
        $promise->expects(self::never())->method('fail');

        $agent->expects(self::once())
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
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        $agent->expects(self::once())
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
        $promise->expects(self::once())->method('success');
        $promise->expects(self::never())->method('fail');

        $builder->expects(self::once())
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
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        $builder->expects(self::once())
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
        $promise->expects(self::once())->method('success');
        $promise->expects(self::never())->method('fail');

        $this->getClusterMock()
            ->expects(self::once())
            ->method('selectCluster')
            ->with($directory)
            ->willReturnCallback(
                function ($c, PromiseInterface $p) use ($directory) {
                    $p->success($this->createMock(ClusterClientInterface::class));

                    return $this->getClusterMock();
                }
            );

        self::assertInstanceOf(
            JobUnit::class,
            $object->configureCluster($directory, $promise)
        );
    }

    public function testConfigureClusterOnError()
    {
        $object = $this->buildObject();

        $directory = $this->createMock(Directory::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        $this->getClusterMock()
            ->expects(self::once())
            ->method('selectCluster')
            ->with($directory)
            ->willThrowException(new \Exception());

        self::assertInstanceOf(
            JobUnit::class,
            $object->configureCluster($directory, $promise)
        );
    }

    public function testConfigureClusterOnErrorOnConfigure()
    {
        $object = $this->buildObject();

        $directory = $this->createMock(Directory::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        $this->getClusterMock()
            ->expects(self::once())
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
            $object->configureCluster($directory, $promise)
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
        $normalizer->expects(self::once())
            ->method('injectData')
            ->with([
                '@class' => Job::class,
                'id' => 'test',
                'project' => ['@class' => Project::class,'id' => 'bar', 'name' => 'hello'],
                'environment' => new Environment('foo'),
                'base_namespace' => 'foo',
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

    public function testUpdateVariablesIn()
    {
        $ori = [
            'foo' => 'foo',
            'bar' => [
                '${foo}',
                '${foo} text ${bar}',
            ],
            '${foo}' => 'text'
        ];

        self::assertInstanceOf(
            JobUnit::class,
            $this->buildObject()->updateVariablesIn(
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
                                '${foo}' => 'text',
                                'paas' => [
                                    'namespace' => 'foo/hello'
                                ]
                            ],
                            $result
                        );
                    },
                    function (\Throwable  $error): never {
                        throw $error;
                    }
                )
            )
        );
    }

    public function testUpdateVariablesInWithNoNamespave()
    {
        $ori = [
            'foo' => 'foo',
            'bar' => [
                '${foo}',
                '${foo} text ${bar}',
            ],
            '${foo}' => 'text'
        ];

        self::assertInstanceOf(
            JobUnit::class,
            $this->buildObject(null)->updateVariablesIn(
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
                                '${foo}' => 'text',
                                'paas' => [
                                    'namespace' => 'hello'
                                ]
                            ],
                            $result
                        );
                    },
                    function (\Throwable  $error): never {
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
            $this->buildObject(null)->updateVariablesIn(
                $ori,
                new Promise(
                    function (array $result) {
                        self::assertEquals(
                            [
                                'paas' => [
                                    'namespace' => 'hello'
                                ]
                            ],
                            $result
                        );
                    },
                    function (\Throwable  $error): never {
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
                    function (\Throwable $error) {
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
            $this->buildObject('foo', ['foo' => 'bar'])->runWithExtra(function ($e) use (&$extra) {
                $extra = $e;
            })
        );

        self::assertEquals(
            ['foo' => 'bar'],
            $extra
        );
    }
}
