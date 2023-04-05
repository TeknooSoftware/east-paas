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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Normalizer;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\JobUnitDenormalizer;
use Teknoo\East\Paas\Job\JobUnit;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Object\ImageRegistry;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\GitRepository;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Contracts\Object\ImageRegistryInterface;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Object\Cluster;
use function func_get_args;
use function in_array;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\JobUnitDenormalizer
 */
class JobUnitDenormalizerTest extends TestCase
{
    public function buildNormalizer(): JobUnitDenormalizer
    {
        return new JobUnitDenormalizer();
    }

    public function testSetDenormalizerBadArgument()
    {
        $this->expectException(\TypeError::class);
        $this->buildNormalizer()->setDenormalizer(new \stdClass());
    }

    public function testSetDenormalizer()
    {
        self::assertInstanceOf(
            JobUnitDenormalizer::class,
            $this->buildNormalizer()->setDenormalizer(
                $this->createMock(DenormalizerInterface::class)
            )
        );
    }

    public function testSupportsDenormalization()
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        self::assertFalse($this->buildNormalizer()->supportsDenormalization(new \stdClass(), 'foo'));
        self::assertFalse($this->buildNormalizer()->supportsDenormalization(['foo'=>'bar'], 'foo'));
        self::assertTrue($this->buildNormalizer()->setDenormalizer($denormalizer)->supportsDenormalization(['foo'=>'bar'], JobUnitInterface::class));
    }

    public function testDenormalizeNotDenormalizer()
    {
        $this->expectException(\RuntimeException::class);
        $this->buildNormalizer()->denormalize(new \stdClass(), 'foo');
    }

    public function testDenormalizeNotArray()
    {
        $this->expectException(\RuntimeException::class);
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $this->buildNormalizer()->setDenormalizer($denormalizer)->denormalize(new \stdClass(), 'foo');
    }

    public function testDenormalizeNotClass()
    {
        $this->expectException(\RuntimeException::class);
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $this->buildNormalizer()->setDenormalizer($denormalizer)->denormalize(['foo' => 'bar'], 'foo');
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

    public function testDenormalizeWithoutSourceRepository()
    {
        $this->expectException(\RuntimeException::class);

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects(self::any())
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
        ];

        $this->buildNormalizer()->setDenormalizer($denormalizer)
            ->denormalize($jobNormalized, JobUnitInterface::class);
    }

    public function testDenormalizeWithoutImageRegistry()
    {
        $this->expectException(\RuntimeException::class);

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects(self::any())
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
        ];

        $this->buildNormalizer()->setDenormalizer($denormalizer)
            ->denormalize($jobNormalized, JobUnitInterface::class);
    }

    public function testDenormalizeWithoutEnvironment()
    {
        $this->expectException(\RuntimeException::class);

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects(self::any())
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
        ];

        $this->buildNormalizer()->setDenormalizer($denormalizer)
            ->denormalize($jobNormalized, JobUnitInterface::class);
    }

    public function testDenormalizeWithoutCluster()
    {
        $this->expectException(\RuntimeException::class);

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects(self::any())
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
        ];

        $this->buildNormalizer()->setDenormalizer($denormalizer)
            ->denormalize($jobNormalized, JobUnitInterface::class);
    }

    public function testDenormalizeWithoutClusterInstance()
    {
        $this->expectException(\RuntimeException::class);

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects(self::any())
            ->method('denormalize')
            ->willReturnOnConsecutiveCalls(
                $srepo = $this->createMock(SourceRepositoryInterface::class),
                $iregistry = $this->createMock(ImageRegistryInterface::class),
                $env = $this->createMock(Environment::class),
                [new \stdClass()],
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
        ];

        $this->buildNormalizer()->setDenormalizer($denormalizer)
            ->denormalize($jobNormalized, JobUnitInterface::class);
    }

    public function testDenormalizeWithoutHistory()
    {
        $this->expectException(\RuntimeException::class);

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects(self::any())
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
        ];

        $this->buildNormalizer()->setDenormalizer($denormalizer)
            ->denormalize($jobNormalized, JobUnitInterface::class);
    }

    public function testDenormalize()
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects(self::exactly(5))
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
        ];

        $jobUnit = new JobUnit(
            id: $id, 
            projectResume: $project, 
            environment: $env, 
            baseNamespace: 'fooBar',
            prefix: 'foobar', 
            sourceRepository: $srepo, 
            imagesRegistry: $iregistry,
            clusters: $clusters,
            variables: ['foo' => 'bar'],
            history: $history
        );
        self::assertEquals(
            $jobUnit,
            $this->buildNormalizer()->setDenormalizer($denormalizer)
                ->denormalize($jobNormalized, JobUnitInterface::class)
        );
    }

    public function testDenormalizeWithHierarchicalNS()
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects(self::exactly(5))
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
            "hierarchical_namespaces" => true,
            "environment" => ['env' => 'bar'],
            "source_repository" => ['url' => 'foo', '@class' => GitRepository::class],
            "images_repository" => ['url' => 'foo', '@class' => ImageRegistry::class],
            "clusters" => [['cluster' => 'bar']],
            "variables" => ['foo' => 'bar'],
            "history" => ['history' => 'bar'],
        ];

        $jobUnit = new JobUnit(
            id: $id,
            projectResume: $project,
            environment: $env,
            baseNamespace: 'fooBar',
            prefix: 'foobar',
            hierarchicalNamespaces: true,
            sourceRepository: $srepo,
            imagesRegistry: $iregistry,
            clusters: $clusters,
            variables: ['foo' => 'bar'],
            history: $history,
        );
        self::assertEquals(
            $jobUnit,
            $this->buildNormalizer()->setDenormalizer($denormalizer)
                ->denormalize($jobNormalized, JobUnitInterface::class)
        );
    }

    public function testDenormalizeWithHierarchicalNSInContext()
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects(self::exactly(5))
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
            "hierarchical_namespaces" => true,
            "environment" => ['env' => 'bar'],
            "source_repository" => ['url' => 'foo', '@class' => GitRepository::class],
            "images_repository" => ['url' => 'foo', '@class' => ImageRegistry::class],
            "clusters" => [['cluster' => 'bar']],
            "variables" => ['foo' => 'bar'],
            "history" => ['history' => 'bar'],
        ];

        $jobUnit = new JobUnit(
            id: $id,
            projectResume: $project,
            environment: $env,
            baseNamespace: 'fooBar',
            prefix: 'foobar',
            hierarchicalNamespaces: true,
            sourceRepository: $srepo,
            imagesRegistry: $iregistry,
            clusters: $clusters,
            variables: ['foo' => 'bar'],
            history: $history,
        );
        self::assertEquals(
            $jobUnit,
            $this->buildNormalizer()->setDenormalizer($denormalizer)
                ->denormalize($jobNormalized, JobUnitInterface::class, null, ['hierarchical_namespaces' => false])
        );
    }

    public function testDenormalizeWithHierarchicalNSInContextAtTrue()
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects(self::exactly(5))
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
        ];

        $jobUnit = new JobUnit(
            id: $id,
            projectResume: $project,
            environment: $env,
            baseNamespace: 'fooBar',
            prefix: 'foobar',
            hierarchicalNamespaces: true,
            sourceRepository: $srepo,
            imagesRegistry: $iregistry,
            clusters: $clusters,
            variables: ['foo' => 'bar'],
            history: $history,
        );
        self::assertEquals(
            $jobUnit,
            $this->buildNormalizer()->setDenormalizer($denormalizer)
                ->denormalize($jobNormalized, JobUnitInterface::class, null, ['hierarchical_namespaces' => true])
        );
    }
}
