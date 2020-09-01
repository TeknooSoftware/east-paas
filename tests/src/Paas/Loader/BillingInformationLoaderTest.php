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

namespace Teknoo\Tests\East\Paas\Loader;

use PHPUnit\Framework\TestCase;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Teknoo\East\Website\DBSource\RepositoryInterface;
use Teknoo\East\Website\Loader\MongoDbCollectionLoaderTrait;
use Teknoo\East\Website\Loader\LoaderInterface;
use Teknoo\East\Website\Loader\TypeLoader;
use Teknoo\East\Paas\Contracts\DbSource\Repository\BillingInformationRepositoryInterface;
use Teknoo\East\Paas\Loader\BillingInformationLoader;
use Teknoo\East\Paas\Object\BillingInformation;
use Teknoo\Tests\East\Website\Loader\LoaderTestTrait;

/**
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers      \Teknoo\East\Paas\Loader\BillingInformationLoader
 */
class BillingInformationLoaderTest extends TestCase
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
            $this->repository = $this->createMock(BillingInformationRepositoryInterface::class);
        }

        return $this->repository;
    }

    /**
     * @return LoaderInterface|TypeLoader
     */
    public function buildLoader(): LoaderInterface
    {
        $repository = $this->getRepositoryMock();
        return new BillingInformationLoader($repository);
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
     * @return LoaderInterface|BillingInformationLoader
     */
    public function buildLoaderWithNotCollectionImplemented(): LoaderInterface
    {
        $repository = $this->getRepositoryMock();
        return new BillingInformationLoader($repository);
    }

    /**
     * @return BillingInformation
     */
    public function getEntity()
    {
        return new BillingInformation();
    }
}
