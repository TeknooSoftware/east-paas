<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\SerializingNormalier;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\JobUnitDenormalizer;
use Teknoo\East\Paas\Job\JobUnit;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Object\DockerRepository;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\GitRepository;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Contracts\Object\ImagesRepositoryInterface;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Object\Cluster;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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

    public function testDenormalizeWithoutSourceRepository()
    {
        $this->expectException(\RuntimeException::class);

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects(self::any())
            ->method('denormalize')
            ->withConsecutive(
                [['url' => 'foo', '@class' => GitRepository::class], GitRepository::class],
                [['url' => 'foo', '@class' => DockerRepository::class], DockerRepository::class],
                [['env' => 'bar'], Environment::class],
                [[['cluster' => 'bar']], Cluster::class.'[]'],
                [['history' => 'bar'], History::class]
            )
            ->willReturnOnConsecutiveCalls(
                null,
                $irepo = $this->createMock(ImagesRepositoryInterface::class),
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
            "images_repository" => ['url' => 'foo', '@class' => DockerRepository::class],
            "clusters" => [['cluster' => 'bar']],
            "variables" => ['foo' => 'bar'],
            "history" => ['history' => 'bar'],
        ];

        $this->buildNormalizer()->setDenormalizer($denormalizer)
            ->denormalize($jobNormalized, JobUnitInterface::class);
    }

    public function testDenormalizeWithoutImageRepository()
    {
        $this->expectException(\RuntimeException::class);

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects(self::any())
            ->method('denormalize')
            ->withConsecutive(
                [['url' => 'foo', '@class' => GitRepository::class], GitRepository::class],
                [['url' => 'foo', '@class' => DockerRepository::class], DockerRepository::class],
                [['env' => 'bar'], Environment::class],
                [[['cluster' => 'bar']], Cluster::class.'[]'],
                [['history' => 'bar'], History::class]
            )
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
            "images_repository" => ['url' => 'foo', '@class' => DockerRepository::class],
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
            ->withConsecutive(
                [['url' => 'foo', '@class' => GitRepository::class], GitRepository::class],
                [['url' => 'foo', '@class' => DockerRepository::class], DockerRepository::class],
                [['env' => 'bar'], Environment::class],
                [[['cluster' => 'bar']], Cluster::class.'[]'],
                [['history' => 'bar'], History::class]
            )
            ->willReturnOnConsecutiveCalls(
                $srepo = $this->createMock(SourceRepositoryInterface::class),
                $irepo = $this->createMock(ImagesRepositoryInterface::class),
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
            "images_repository" => ['url' => 'foo', '@class' => DockerRepository::class],
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
            ->withConsecutive(
                [['url' => 'foo', '@class' => GitRepository::class], GitRepository::class],
                [['url' => 'foo', '@class' => DockerRepository::class], DockerRepository::class],
                [['env' => 'bar'], Environment::class],
                [[['cluster' => 'bar']], Cluster::class.'[]'],
                [['history' => 'bar'], History::class]
            )
            ->willReturnOnConsecutiveCalls(
                $srepo = $this->createMock(SourceRepositoryInterface::class),
                $irepo = $this->createMock(ImagesRepositoryInterface::class),
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
            "images_repository" => ['url' => 'foo', '@class' => DockerRepository::class],
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
            ->withConsecutive(
                [['url' => 'foo', '@class' => GitRepository::class], GitRepository::class],
                [['url' => 'foo', '@class' => DockerRepository::class], DockerRepository::class],
                [['env' => 'bar'], Environment::class],
                [[['cluster' => 'bar']], Cluster::class.'[]'],
                [['history' => 'bar'], History::class]
            )
            ->willReturnOnConsecutiveCalls(
                $srepo = $this->createMock(SourceRepositoryInterface::class),
                $irepo = $this->createMock(ImagesRepositoryInterface::class),
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
            "images_repository" => ['url' => 'foo', '@class' => DockerRepository::class],
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
            ->withConsecutive(
                [['url' => 'foo', '@class' => GitRepository::class], GitRepository::class],
                [['url' => 'foo', '@class' => DockerRepository::class], DockerRepository::class],
                [['env' => 'bar'], Environment::class],
                [[['cluster' => 'bar']], Cluster::class.'[]'],
                [['history' => 'bar'], History::class]
            )
            ->willReturnOnConsecutiveCalls(
                $srepo = $this->createMock(SourceRepositoryInterface::class),
                $irepo = $this->createMock(ImagesRepositoryInterface::class),
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
            "images_repository" => ['url' => 'foo', '@class' => DockerRepository::class],
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
            ->withConsecutive(
                [['url' => 'foo', '@class' => GitRepository::class], GitRepository::class],
                [['url' => 'foo', '@class' => DockerRepository::class], DockerRepository::class],
                [['env' => 'bar'], Environment::class],
                [[['cluster' => 'bar']], Cluster::class.'[]'],
                [['history' => 'bar'], History::class]
            )
            ->willReturnOnConsecutiveCalls(
                $srepo = $this->createMock(SourceRepositoryInterface::class),
                $irepo = $this->createMock(ImagesRepositoryInterface::class),
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
            "images_repository" => ['url' => 'foo', '@class' => DockerRepository::class],
            "clusters" => [['cluster' => 'bar']],
            "variables" => ['foo' => 'bar'],
            "history" => ['history' => 'bar'],
        ];

        $jobUnit = new JobUnit($id, $project, $env, $srepo, $irepo, $clusters, ['foo' => 'bar'], $history);
        self::assertEquals(
            $jobUnit,
            $this->buildNormalizer()->setDenormalizer($denormalizer)
                ->denormalize($jobNormalized, JobUnitInterface::class)
        );
    }
}
