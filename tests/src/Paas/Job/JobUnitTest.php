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
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
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
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Paas\Contracts\Cluster\ClientInterface as ClusterClientInterface;
use Teknoo\East\Paas\Contracts\Container\BuilderInterface as ImageBuilder;
use Teknoo\East\Paas\Job\JobUnit;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Paas\Contracts\Object\ImagesRepositoryInterface;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;

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
     * @var ImagesRepositoryInterface
     */
    private $imagesRepository;

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
     * @return ImagesRepositoryInterface
     */
    public function getImagesRepositoryMock(): ImagesRepositoryInterface
    {
        if (!$this->imagesRepository instanceof ImagesRepositoryInterface) {
            $this->imagesRepository = $this->createMock(ImagesRepositoryInterface::class);

            $this->imagesRepository->expects(self::any())->method('getApiUrl')->willReturn('foo');
            $this->imagesRepository->expects(self::any())->method('getIdentity')->willReturn(
                $this->createMock(IdentityInterface::class)
            );
        }

        return $this->imagesRepository;
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

    private function buildObject()
    {
        return (new JobUnit(
            'test',
            ['@class' => Project::class,'id' => 'bar', 'name' => 'hello'],
            new Environment('foo'),
            $this->getSourceRepositoryMock(),
            $this->getImagesRepositoryMock(),
            [$this->getClusterMock()],
            [
                'foo' => 'bar',
                'bar' => 'FOO',
            ],
            new History(null, 'foo', new \DateTimeImmutable('2018-05-01'))
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
            ->with('foo', self::callback(
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
            ->with('foo', self::callback(
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

        $client = $this->createMock(ClusterClientInterface::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success');
        $promise->expects(self::never())->method('fail');

        $this->getClusterMock()
            ->expects(self::once())
            ->method('configureCluster')
            ->with($client)
            ->willReturnCallback(
                function ($c, PromiseInterface $p) use ($client) {
                    $p->success($client);

                    return $this->getClusterMock();
                }
            );

        self::assertInstanceOf(
            JobUnit::class,
            $object->configureCluster($client, $promise)
        );
    }

    public function testConfigureClusterOnError()
    {
        $object = $this->buildObject();

        $client = $this->createMock(ClusterClientInterface::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        $this->getClusterMock()
            ->expects(self::once())
            ->method('configureCluster')
            ->with($client)
            ->willThrowException(new \Exception());

        self::assertInstanceOf(
            JobUnit::class,
            $object->configureCluster($client, $promise)
        );
    }

    public function testConfigureClusterOnErrorOnConfigure()
    {
        $object = $this->buildObject();

        $client = $this->createMock(ClusterClientInterface::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        $this->getClusterMock()
            ->expects(self::once())
            ->method('configureCluster')
            ->with($client)
            ->willReturnCallback(
                function ($c, PromiseInterface $p) {
                    $p->fail(new \Exception());

                    return $this->getClusterMock();
                }
            );

        self::assertInstanceOf(
            JobUnit::class,
            $object->configureCluster($client, $promise)
        );
    }

    public function testPrepareUrlBadUrl()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->prepareUrl(new \stdClass(), $this->createMock(PromiseInterface::class));
    }

    public function testPrepareUrlBadPromise()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->prepareUrl('url', new \stdClass());
    }

    public function testPrepareUrl()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())
            ->method('success')
            ->with('https://foo.bar/project/bar/environment/foo/job/test/addHistory')
            ->willReturnSelf();

        $object = $this->buildObject();

        self::assertInstanceOf(
            JobUnit::class,
            $object->prepareUrl('https://foo.bar/project/{projectId}/environment/{envName}/job/{jobId}/addHistory', $promise)
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
                'source_repository' => $this->getSourceRepositoryMock(),
                'images_repository' => $this->getImagesRepositoryMock(),
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
                                '${foo}' => 'text'
                            ],
                            $result
                        );
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
                            [],
                            $result
                        );
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
}
