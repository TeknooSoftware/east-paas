<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Loader;

use PHPUnit\Framework\TestCase;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Teknoo\East\Website\DBSource\RepositoryInterface;
use Teknoo\East\Website\Loader\MongoDbCollectionLoaderTrait;
use Teknoo\East\Website\Loader\LoaderInterface;
use Teknoo\East\Website\Loader\TypeLoader;
use Teknoo\East\Paas\Contracts\DbSource\Repository\JobRepositoryInterface;
use Teknoo\East\Paas\Loader\JobLoader;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\Tests\East\Website\Loader\LoaderTestTrait;

/**
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers      \Teknoo\East\Paas\Loader\JobLoader
 */
class JobLoaderTest extends TestCase
{
    use LoaderTestTrait;

    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|RepositoryInterface
     */
    public function getRepositoryMock(): RepositoryInterface
    {
        if (!$this->repository instanceof RepositoryInterface) {
            $this->repository = $this->createMock(JobRepositoryInterface::class);
        }

        return $this->repository;
    }

    /**
     * @return LoaderInterface|TypeLoader
     */
    public function buildLoader(): LoaderInterface
    {
        $repository = $this->getRepositoryMock();
        return new JobLoader($repository);
    }

    /**
     * @return LoaderInterface|TypeLoader
     */
    public function buildLoaderWithBadCollectionImplementation(): LoaderInterface
    {
        $repository = $this->getRepositoryMock();
        return new class($repository) extends TypeLoader {
            protected function prepareQuery(
                array &$criteria,
                ?array $order,
                ?int $limit,
                ?int $offset
            ) {
                return [];
            }
        };
    }

    /**
     * @return LoaderInterface|JobLoader
     */
    public function buildLoaderWithNotCollectionImplemented(): LoaderInterface
    {
        $repository = $this->getRepositoryMock();
        return new JobLoader($repository);
    }

    /**
     * @return Job
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function getEntity()
    {
        return new Job($this->createMock(Project::class));
    }
}
