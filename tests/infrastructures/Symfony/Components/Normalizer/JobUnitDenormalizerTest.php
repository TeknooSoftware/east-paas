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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Normalizer;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Object\ImageRegistryInterface;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\Exception\NotSupportedException;
use Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\JobUnitDenormalizer;
use Teknoo\East\Paas\Job\JobUnit;
use Teknoo\East\Paas\Object\AccountQuota;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\GitRepository;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Object\ImageRegistry;
use Teknoo\East\Paas\Object\Job;
use TypeError;

use function func_get_args;
use function in_array;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(JobUnitDenormalizer::class)]
class JobUnitDenormalizerTest extends TestCase
{
    public function buildNormalizer(): JobUnitDenormalizer
    {
        return new JobUnitDenormalizer();
    }

    public function testSetDenormalizerBadArgument(): void
    {
        $this->expectException(TypeError::class);
        $this->buildNormalizer()->setDenormalizer(new stdClass());
    }

    public function testSetDenormalizer(): void
    {
        $this->assertInstanceOf(JobUnitDenormalizer::class, $this->buildNormalizer()->setDenormalizer(
            $this->createMock(DenormalizerInterface::class)
        ));
    }

    public function testSupportsDenormalization(): void
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $this->assertFalse($this->buildNormalizer()->supportsDenormalization(new stdClass(), 'foo'));
        $this->assertFalse($this->buildNormalizer()->supportsDenormalization(['foo' => 'bar'], 'foo'));
        $this->assertTrue($this->buildNormalizer()->setDenormalizer($denormalizer)->supportsDenormalization(['foo' => 'bar'], JobUnitInterface::class));
    }

    public function testDenormalizeNotDenormalizer(): void
    {
        $this->expectException(RuntimeException::class);
        $this->buildNormalizer()->denormalize(new stdClass(), 'foo');
    }

    public function testDenormalizeNotArray(): void
    {
        $this->expectException(RuntimeException::class);
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $this->buildNormalizer()->setDenormalizer($denormalizer)->denormalize(new stdClass(), 'foo');
    }

    public function testDenormalizeNotClass(): void
    {
        $this->expectException(RuntimeException::class);
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $this->buildNormalizer()->setDenormalizer($denormalizer)->denormalize(['foo' => 'bar'], 'foo');
    }


    public function testDenormalizeNotId(): void
    {
        $this->expectException(NotSupportedException::class);
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $this->buildNormalizer()->setDenormalizer($denormalizer)->denormalize(['foo' => 'bar'], JobUnitInterface::class);
    }

    public function testDenormalizeNotStringId(): void
    {
        $this->expectException(NotSupportedException::class);
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $this->buildNormalizer()->setDenormalizer($denormalizer)->denormalize(['id' => new \stdClass()], JobUnitInterface::class);
    }

    public function testDenormalizeNotValidProject(): void
    {
        $this->expectException(NotSupportedException::class);
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $this->buildNormalizer()->setDenormalizer($denormalizer)->denormalize(['id' => '123'], JobUnitInterface::class);
    }

    public function testDenormalizeNotValidrefix(): void
    {
        $this->expectException(NotSupportedException::class);
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $id = "c529be6e38cf3e40bea008eaee8bfb4f";
        $project = [
            "@class" => "Teknoo\\Paas\\Object\\Project",
            "id" => "a8c295574b4232148ee343caf08f1cd4",
            "name" => "paas_test"
        ];
        $jobNormalized = [
            "@class" => Job::class,
            "id" => $id,
            "project" => $project,
            'prefix' => new \stdClass()
        ];

        $this->buildNormalizer()->setDenormalizer($denormalizer)->denormalize($jobNormalized, JobUnitInterface::class);
    }

    public function buildParametersVerification(
        mixed $srepo,
        mixed $iregistry,
        mixed $env,
        mixed $clusters,
        mixed $history
    ): callable {
        return function () use ($srepo, $iregistry, $env, $clusters, $history) {
            $args = func_get_args();
            $expectedArgs = [
                [['url' => 'foo', '@class' => GitRepository::class], GitRepository::class],
                [['url' => 'foo', '@class' => ImageRegistry::class], ImageRegistry::class],
                [['env' => 'bar'], Environment::class],
                [[['cluster' => 'bar']], Cluster::class.'[]'],
                [['history' => 'bar'], History::class]
            ];

            if (in_array($args, $expectedArgs)) {
                throw new InvalidArgumentException('Not expected argument');
            }

            return match ($args[1]) {
                GitRepository::class => $srepo,
                ImageRegistry::class => $iregistry,
                Environment::class => $env,
                Cluster::class.'[]' => $clusters,
                History::class => $history,
            };
        };
    }

    public function testDenormalizeWithoutSourceRepositoryDefined(): void
    {
        $this->expectException(RuntimeException::class);

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer
            ->method('denormalize')
            ->willReturnOnConsecutiveCalls(
                null,
                $iregistry = $this->createMock(ImageRegistryInterface::class),
                $env = $this->createMock(Environment::class),
                $clusters = [$this->createMock(Cluster::class)],
                $history = $this->createMock(History::class)
            );

        $id = "c529be6e38cf3e40bea008eaee8bfb4f";
        $project = [
            "@class" => "Teknoo\\Paas\\Object\\Project",
            "id" => "a8c295574b4232148ee343caf08f1cd4",
            "name" => "paas_test"
        ];
        $jobNormalized = [
            "@class" => Job::class,
            "id" => $id,
            "project" => $project,
            "environment" => ['env' => 'bar'],
            "source_repository" => ['url' => 'foo'],
            "images_repository" => ['url' => 'foo', '@class' => ImageRegistry::class],
            "clusters" => [['cluster' => 'bar']],
            "variables" => ['foo' => 'bar'],
            "history" => ['history' => 'bar'],
            "quotas" => [
                [
                    'category' => 'compute',
                    'type' => 'cpu',
                    'capacity' => '5',
                ]
            ]
        ];

        $this->buildNormalizer()->setDenormalizer($denormalizer)
            ->denormalize($jobNormalized, JobUnitInterface::class);
    }

    public function testDenormalizeWithoutSourceRepositoryDenormalized(): void
    {
        $this->expectException(RuntimeException::class);

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer
            ->method('denormalize')
            ->willReturnOnConsecutiveCalls(
                null,
                $iregistry = $this->createMock(ImageRegistryInterface::class),
                $env = $this->createMock(Environment::class),
                $clusters = [$this->createMock(Cluster::class)],
                $history = $this->createMock(History::class)
            );

        $id = "c529be6e38cf3e40bea008eaee8bfb4f";
        $project = [
            "@class" => "Teknoo\\Paas\\Object\\Project",
            "id" => "a8c295574b4232148ee343caf08f1cd4",
            "name" => "paas_test"
        ];
        $jobNormalized = [
            "@class" => Job::class,
            "id" => $id,
            "project" => $project,
            "environment" => ['env' => 'bar'],
            "source_repository" => ['url' => 'foo', '@class' => GitRepository::class],
            "images_repository" => ['url' => 'foo', '@class' => ImageRegistry::class],
            "clusters" => [['cluster' => 'bar']],
            "variables" => ['foo' => 'bar'],
            "history" => ['history' => 'bar'],
            "quotas" => [
                [
                    'category' => 'compute',
                    'type' => 'cpu',
                    'capacity' => '5',
                ]
            ]
        ];

        $this->buildNormalizer()->setDenormalizer($denormalizer)
            ->denormalize($jobNormalized, JobUnitInterface::class);
    }

    public function testDenormalizeWithoutImageRegistryDefined(): void
    {
        $this->expectException(RuntimeException::class);

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer
            ->method('denormalize')
            ->willReturnOnConsecutiveCalls(
                $srepo = $this->createMock(SourceRepositoryInterface::class),
                null,
                $env = $this->createMock(Environment::class),
                $clusters = [$this->createMock(Cluster::class)],
                $history = $this->createMock(History::class)
            );

        $id = "c529be6e38cf3e40bea008eaee8bfb4f";
        $project = [
            "@class" => "Teknoo\\Paas\\Object\\Project",
            "id" => "a8c295574b4232148ee343caf08f1cd4",
            "name" => "paas_test"
        ];
        $jobNormalized = [
            "@class" => Job::class,
            "id" => $id,
            "project" => $project,
            "environment" => ['env' => 'bar'],
            "source_repository" => ['url' => 'foo', '@class' => GitRepository::class],
            "images_repository" => ['url' => 'foo'],
            "clusters" => [['cluster' => 'bar']],
            "variables" => ['foo' => 'bar'],
            "history" => ['history' => 'bar'],
            "quotas" => [
                [
                    'category' => 'compute',
                    'type' => 'cpu',
                    'capacity' => '5',
                ]
            ]
        ];

        $this->buildNormalizer()->setDenormalizer($denormalizer)
            ->denormalize($jobNormalized, JobUnitInterface::class);
    }

    public function testDenormalizeWithoutImageRegistryDenormalized(): void
    {
        $this->expectException(RuntimeException::class);

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer
            ->method('denormalize')
            ->willReturnOnConsecutiveCalls(
                $srepo = $this->createMock(SourceRepositoryInterface::class),
                null,
                $env = $this->createMock(Environment::class),
                $clusters = [$this->createMock(Cluster::class)],
                $history = $this->createMock(History::class)
            );

        $id = "c529be6e38cf3e40bea008eaee8bfb4f";
        $project = [
            "@class" => "Teknoo\\Paas\\Object\\Project",
            "id" => "a8c295574b4232148ee343caf08f1cd4",
            "name" => "paas_test"
        ];
        $jobNormalized = [
            "@class" => Job::class,
            "id" => $id,
            "project" => $project,
            "environment" => ['env' => 'bar'],
            "source_repository" => ['url' => 'foo', '@class' => GitRepository::class],
            "images_repository" => ['url' => 'foo', '@class' => ImageRegistry::class],
            "clusters" => [['cluster' => 'bar']],
            "variables" => ['foo' => 'bar'],
            "history" => ['history' => 'bar'],
            "quotas" => [
                [
                    'category' => 'compute',
                    'type' => 'cpu',
                    'capacity' => '5',
                ]
            ]
        ];

        $this->buildNormalizer()->setDenormalizer($denormalizer)
            ->denormalize($jobNormalized, JobUnitInterface::class);
    }

    public function testDenormalizeWithoutEnvironmentDefined(): void
    {
        $this->expectException(RuntimeException::class);

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer
            ->method('denormalize')
            ->willReturnOnConsecutiveCalls(
                $srepo = $this->createMock(SourceRepositoryInterface::class),
                $iregistry = $this->createMock(ImageRegistryInterface::class),
                null,
                $clusters = [$this->createMock(Cluster::class)],
                $history = $this->createMock(History::class)
            );

        $id = "c529be6e38cf3e40bea008eaee8bfb4f";
        $project = [
            "@class" => "Teknoo\\Paas\\Object\\Project",
            "id" => "a8c295574b4232148ee343caf08f1cd4",
            "name" => "paas_test"
        ];
        $jobNormalized = [
            "@class" => Job::class,
            "id" => $id,
            "project" => $project,
            "environment" => 'foo',
            "source_repository" => ['url' => 'foo', '@class' => GitRepository::class],
            "images_repository" => ['url' => 'foo', '@class' => ImageRegistry::class],
            "clusters" => [['cluster' => 'bar']],
            "variables" => ['foo' => 'bar'],
            "history" => ['history' => 'bar'],
            "quotas" => [
                [
                    'category' => 'compute',
                    'type' => 'cpu',
                    'capacity' => '5',
                ]
            ]
        ];

        $this->buildNormalizer()->setDenormalizer($denormalizer)
            ->denormalize($jobNormalized, JobUnitInterface::class);
    }

    public function testDenormalizeWithoutEnvironmentDenormalized(): void
    {
        $this->expectException(RuntimeException::class);

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer
            ->method('denormalize')
            ->willReturnOnConsecutiveCalls(
                $srepo = $this->createMock(SourceRepositoryInterface::class),
                $iregistry = $this->createMock(ImageRegistryInterface::class),
                null,
                $clusters = [$this->createMock(Cluster::class)],
                $history = $this->createMock(History::class)
            );

        $id = "c529be6e38cf3e40bea008eaee8bfb4f";
        $project = [
            "@class" => "Teknoo\\Paas\\Object\\Project",
            "id" => "a8c295574b4232148ee343caf08f1cd4",
            "name" => "paas_test"
        ];
        $jobNormalized = [
            "@class" => Job::class,
            "id" => $id,
            "project" => $project,
            "environment" => ['env' => 'bar'],
            "source_repository" => ['url' => 'foo', '@class' => GitRepository::class],
            "images_repository" => ['url' => 'foo', '@class' => ImageRegistry::class],
            "clusters" => [['cluster' => 'bar']],
            "variables" => ['foo' => 'bar'],
            "history" => ['history' => 'bar'],
            "quotas" => [
                [
                    'category' => 'compute',
                    'type' => 'cpu',
                    'capacity' => '5',
                ]
            ]
        ];

        $this->buildNormalizer()->setDenormalizer($denormalizer)
            ->denormalize($jobNormalized, JobUnitInterface::class);
    }

    public function testDenormalizeWithoutCluster(): void
    {
        $this->expectException(RuntimeException::class);

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer
            ->method('denormalize')
            ->willReturnOnConsecutiveCalls(
                $srepo = $this->createMock(SourceRepositoryInterface::class),
                $iregistry = $this->createMock(ImageRegistryInterface::class),
                $env = $this->createMock(Environment::class),
                [],
                $history = $this->createMock(History::class)
            );

        $id = "c529be6e38cf3e40bea008eaee8bfb4f";
        $project = [
            "@class" => "Teknoo\\Paas\\Object\\Project",
            "id" => "a8c295574b4232148ee343caf08f1cd4",
            "name" => "paas_test"
        ];
        $jobNormalized = [
            "@class" => Job::class,
            "id" => $id,
            "project" => $project,
            "environment" => ['env' => 'bar'],
            "source_repository" => ['url' => 'foo', '@class' => GitRepository::class],
            "images_repository" => ['url' => 'foo', '@class' => ImageRegistry::class],
            "clusters" => [['cluster' => 'bar']],
            "variables" => ['foo' => 'bar'],
            "history" => ['history' => 'bar'],
            "quotas" => [
                [
                    'category' => 'compute',
                    'type' => 'cpu',
                    'capacity' => '5',
                ]
            ]
        ];

        $this->buildNormalizer()->setDenormalizer($denormalizer)
            ->denormalize($jobNormalized, JobUnitInterface::class);
    }

    public function testDenormalizeWithoutClusterInstance(): void
    {
        $this->expectException(RuntimeException::class);

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer
            ->method('denormalize')
            ->willReturnOnConsecutiveCalls(
                $srepo = $this->createMock(SourceRepositoryInterface::class),
                $iregistry = $this->createMock(ImageRegistryInterface::class),
                $env = $this->createMock(Environment::class),
                [new stdClass()],
                $history = $this->createMock(History::class)
            );

        $id = "c529be6e38cf3e40bea008eaee8bfb4f";
        $project = [
            "@class" => "Teknoo\\Paas\\Object\\Project",
            "id" => "a8c295574b4232148ee343caf08f1cd4",
            "name" => "paas_test"
        ];
        $jobNormalized = [
            "@class" => Job::class,
            "id" => $id,
            "project" => $project,
            "environment" => ['env' => 'bar'],
            "source_repository" => ['url' => 'foo', '@class' => GitRepository::class],
            "images_repository" => ['url' => 'foo', '@class' => ImageRegistry::class],
            "clusters" => [['cluster' => 'bar']],
            "variables" => ['foo' => 'bar'],
            "history" => ['history' => 'bar'],
            "quotas" => [
                [
                    'category' => 'compute',
                    'type' => 'cpu',
                    'capacity' => '5',
                ]
            ]
        ];

        $this->buildNormalizer()->setDenormalizer($denormalizer)
            ->denormalize($jobNormalized, JobUnitInterface::class);
    }

    public function testDenormalizeWithoutHistory(): void
    {
        $this->expectException(RuntimeException::class);

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer
            ->method('denormalize')
            ->willReturnOnConsecutiveCalls(
                $srepo = $this->createMock(SourceRepositoryInterface::class),
                $iregistry = $this->createMock(ImageRegistryInterface::class),
                $env = $this->createMock(Environment::class),
                $clusters = [$this->createMock(Cluster::class)],
                null
            );

        $id = "c529be6e38cf3e40bea008eaee8bfb4f";
        $project = [
            "@class" => "Teknoo\\Paas\\Object\\Project",
            "id" => "a8c295574b4232148ee343caf08f1cd4",
            "name" => "paas_test"
        ];
        $jobNormalized = [
            "@class" => Job::class,
            "id" => $id,
            "project" => $project,
            "environment" => ['env' => 'bar'],
            "source_repository" => ['url' => 'foo', '@class' => GitRepository::class],
            "images_repository" => ['url' => 'foo', '@class' => ImageRegistry::class],
            "clusters" => [['cluster' => 'bar']],
            "variables" => ['foo' => 'bar'],
            "history" => ['history' => 'bar'],
            "quotas" => [
                [
                    'category' => 'compute',
                    'type' => 'cpu',
                    'capacity' => '5',
                ]
            ]
        ];

        $this->buildNormalizer()->setDenormalizer($denormalizer)
            ->denormalize($jobNormalized, JobUnitInterface::class);
    }

    public function testDenormalize(): void
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects($this->exactly(5))
            ->method('denormalize')
            ->willReturnCallback(
                $this->buildParametersVerification(
                    $srepo = $this->createMock(SourceRepositoryInterface::class),
                    $iregistry = $this->createMock(ImageRegistryInterface::class),
                    $env = $this->createMock(Environment::class),
                    $clusters = [$this->createMock(Cluster::class)],
                    $history = $this->createMock(History::class),
                )
            );

        $id = "c529be6e38cf3e40bea008eaee8bfb4f";
        $project = [
            "@class" => "Teknoo\\Paas\\Object\\Project",
            "id" => "a8c295574b4232148ee343caf08f1cd4",
            "name" => "paas_test"
        ];
        $jobNormalized = [
            "@class" => Job::class,
            "id" => $id,
            "project" => $project,
            "base_namespace" => 'fooBar',
            "prefix" => 'foobar',
            "environment" => ['env' => 'bar'],
            "source_repository" => ['url' => 'foo', '@class' => GitRepository::class],
            "images_repository" => ['url' => 'foo', '@class' => ImageRegistry::class],
            "clusters" => [['cluster' => 'bar']],
            "variables" => ['foo' => 'bar'],
            "history" => ['history' => 'bar'],
            "quotas" => [
                [
                    'category' => 'compute',
                    'type' => 'cpu',
                    'capacity' => '5',
                ]
            ]
        ];

        $jobUnit = new JobUnit(
            id: $id,
            projectResume: $project,
            environment: $env,
            prefix: 'foobar',
            sourceRepository: $srepo,
            imagesRegistry: $iregistry,
            clusters: $clusters,
            variables: ['foo' => 'bar'],
            history: $history,
            quotas: [
                new AccountQuota('compute', 'cpu', '5'),
            ]
        );
        $this->assertEquals($jobUnit, $this->buildNormalizer()->setDenormalizer($denormalizer)
            ->denormalize($jobNormalized, JobUnitInterface::class));
    }

    public function testDenormalizeWithoutVariables(): void
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects($this->exactly(5))
            ->method('denormalize')
            ->willReturnCallback(
                $this->buildParametersVerification(
                    $srepo = $this->createMock(SourceRepositoryInterface::class),
                    $iregistry = $this->createMock(ImageRegistryInterface::class),
                    $env = $this->createMock(Environment::class),
                    $clusters = [$this->createMock(Cluster::class)],
                    $history = $this->createMock(History::class),
                )
            );

        $id = "c529be6e38cf3e40bea008eaee8bfb4f";
        $project = [
            "@class" => "Teknoo\\Paas\\Object\\Project",
            "id" => "a8c295574b4232148ee343caf08f1cd4",
            "name" => "paas_test"
        ];
        $jobNormalized = [
            "@class" => Job::class,
            "id" => $id,
            "project" => $project,
            "base_namespace" => 'fooBar',
            "prefix" => 'foobar',
            "environment" => ['env' => 'bar'],
            "source_repository" => ['url' => 'foo', '@class' => GitRepository::class],
            "images_repository" => ['url' => 'foo', '@class' => ImageRegistry::class],
            "clusters" => [['cluster' => 'bar']],
            "history" => ['history' => 'bar'],
            "quotas" => [
                [
                    'category' => 'compute',
                    'type' => 'cpu',
                    'capacity' => '5',
                ]
            ]
        ];

        $jobUnit = new JobUnit(
            id: $id,
            projectResume: $project,
            environment: $env,
            prefix: 'foobar',
            sourceRepository: $srepo,
            imagesRegistry: $iregistry,
            clusters: $clusters,
            variables: [],
            history: $history,
            quotas: [
                new AccountQuota('compute', 'cpu', '5'),
            ]
        );
        $this->assertEquals($jobUnit, $this->buildNormalizer()->setDenormalizer($denormalizer)
            ->denormalize($jobNormalized, JobUnitInterface::class));
    }

    public function testDenormalizeWithInvalidQuota(): void
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects($this->exactly(5))
            ->method('denormalize')
            ->willReturnCallback(
                $this->buildParametersVerification(
                    $srepo = $this->createMock(SourceRepositoryInterface::class),
                    $iregistry = $this->createMock(ImageRegistryInterface::class),
                    $env = $this->createMock(Environment::class),
                    $clusters = [$this->createMock(Cluster::class)],
                    $history = $this->createMock(History::class),
                )
            );

        $id = "c529be6e38cf3e40bea008eaee8bfb4f";
        $project = [
            "@class" => "Teknoo\\Paas\\Object\\Project",
            "id" => "a8c295574b4232148ee343caf08f1cd4",
            "name" => "paas_test"
        ];
        $jobNormalized = [
            "@class" => Job::class,
            "id" => $id,
            "project" => $project,
            "base_namespace" => 'fooBar',
            "prefix" => 'foobar',
            "environment" => ['env' => 'bar'],
            "source_repository" => ['url' => 'foo', '@class' => GitRepository::class],
            "images_repository" => ['url' => 'foo', '@class' => ImageRegistry::class],
            "clusters" => [['cluster' => 'bar']],
            "history" => ['history' => 'bar'],
            "quotas" => 123
        ];

        $jobUnit = new JobUnit(
            id: $id,
            projectResume: $project,
            environment: $env,
            prefix: 'foobar',
            sourceRepository: $srepo,
            imagesRegistry: $iregistry,
            clusters: $clusters,
            variables: ['foo' => 'bar'],
            history: $history,
            quotas: [
                new AccountQuota('compute', 'cpu', '5'),
            ]
        );

        $this->expectException(NotSupportedException::class);
        $this->buildNormalizer()->setDenormalizer($denormalizer)->denormalize($jobNormalized, JobUnitInterface::class);
    }

    public function testDenormalizeWithInvalidExtra(): void
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects($this->exactly(5))
            ->method('denormalize')
            ->willReturnCallback(
                $this->buildParametersVerification(
                    $srepo = $this->createMock(SourceRepositoryInterface::class),
                    $iregistry = $this->createMock(ImageRegistryInterface::class),
                    $env = $this->createMock(Environment::class),
                    $clusters = [$this->createMock(Cluster::class)],
                    $history = $this->createMock(History::class),
                )
            );

        $id = "c529be6e38cf3e40bea008eaee8bfb4f";
        $project = [
            "@class" => "Teknoo\\Paas\\Object\\Project",
            "id" => "a8c295574b4232148ee343caf08f1cd4",
            "name" => "paas_test"
        ];
        $jobNormalized = [
            "@class" => Job::class,
            "id" => $id,
            "project" => $project,
            "base_namespace" => 'fooBar',
            "prefix" => 'foobar',
            "environment" => ['env' => 'bar'],
            "source_repository" => ['url' => 'foo', '@class' => GitRepository::class],
            "images_repository" => ['url' => 'foo', '@class' => ImageRegistry::class],
            "clusters" => [['cluster' => 'bar']],
            "history" => ['history' => 'bar'],
            "quotas" => [
                [
                    'category' => 'compute',
                    'type' => 'cpu',
                    'capacity' => '5',
                ]
            ],
            "extra" => 123
        ];

        $jobUnit = new JobUnit(
            id: $id,
            projectResume: $project,
            environment: $env,
            prefix: 'foobar',
            sourceRepository: $srepo,
            imagesRegistry: $iregistry,
            clusters: $clusters,
            variables: ['foo' => 'bar'],
            history: $history,
            quotas: [
                new AccountQuota('compute', 'cpu', '5'),
            ]
        );

        $this->expectException(NotSupportedException::class);
        $this->buildNormalizer()->setDenormalizer($denormalizer)->denormalize($jobNormalized, JobUnitInterface::class);
    }

    public function testDenormalizeWithInvalidDefaults(): void
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects($this->exactly(5))
            ->method('denormalize')
            ->willReturnCallback(
                $this->buildParametersVerification(
                    $srepo = $this->createMock(SourceRepositoryInterface::class),
                    $iregistry = $this->createMock(ImageRegistryInterface::class),
                    $env = $this->createMock(Environment::class),
                    $clusters = [$this->createMock(Cluster::class)],
                    $history = $this->createMock(History::class),
                )
            );

        $id = "c529be6e38cf3e40bea008eaee8bfb4f";
        $project = [
            "@class" => "Teknoo\\Paas\\Object\\Project",
            "id" => "a8c295574b4232148ee343caf08f1cd4",
            "name" => "paas_test"
        ];
        $jobNormalized = [
            "@class" => Job::class,
            "id" => $id,
            "project" => $project,
            "base_namespace" => 'fooBar',
            "prefix" => 'foobar',
            "environment" => ['env' => 'bar'],
            "source_repository" => ['url' => 'foo', '@class' => GitRepository::class],
            "images_repository" => ['url' => 'foo', '@class' => ImageRegistry::class],
            "clusters" => [['cluster' => 'bar']],
            "history" => ['history' => 'bar'],
            "quotas" => [
                [
                    'category' => 'compute',
                    'type' => 'cpu',
                    'capacity' => '5',
                ]
            ],
            "defaults" => 123
        ];

        $jobUnit = new JobUnit(
            id: $id,
            projectResume: $project,
            environment: $env,
            prefix: 'foobar',
            sourceRepository: $srepo,
            imagesRegistry: $iregistry,
            clusters: $clusters,
            variables: ['foo' => 'bar'],
            history: $history,
            quotas: [
                new AccountQuota('compute', 'cpu', '5'),
            ]
        );

        $this->expectException(NotSupportedException::class);
        $this->buildNormalizer()->setDenormalizer($denormalizer)->denormalize($jobNormalized, JobUnitInterface::class);
    }

    public function testGetSupportedTypes(): void
    {
        $this->assertIsArray($this->buildNormalizer()->getSupportedTypes('array'));
    }
}
