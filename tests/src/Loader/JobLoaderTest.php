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

namespace Teknoo\Tests\East\Paas\Loader;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\Contracts\DBSource\RepositoryInterface;
use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\JobRepositoryInterface;
use Teknoo\East\Paas\Loader\JobLoader;
use Teknoo\East\Paas\Object\Job;
use Teknoo\States\Proxy\Exception\StateNotFound;
use Teknoo\Tests\East\Common\Loader\LoaderTestTrait;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(JobLoader::class)]
class JobLoaderTest extends TestCase
{
    use LoaderTestTrait;

    private (RepositoryInterface&MockObject)|null $repository = null;

    public function getRepositoryMock(): RepositoryInterface&MockObject
    {
        if (!$this->repository instanceof RepositoryInterface) {
            $this->repository = $this->createMock(JobRepositoryInterface::class);
        }

        return $this->repository;
    }

    public function buildLoader(): LoaderInterface&JobLoader
    {
        $repository = $this->getRepositoryMock();
        return new JobLoader($repository);
    }

    public function buildLoaderWithBadCollectionImplementation(): LoaderInterface&JobLoader
    {
        $repository = $this->getRepositoryMock();
        return new class ($repository) extends JobLoader {
            protected function prepareQuery(
                array &$criteria,
                ?array $order,
                ?int $limit,
                ?int $offset
            ): array {
                return [];
            }
        };
    }

    public function buildLoaderWithNotCollectionImplemented(): LoaderInterface&JobLoader
    {
        $repository = $this->getRepositoryMock();
        return new JobLoader($repository);
    }

    /**
     * @return Job
     * @throws StateNotFound
     */
    public function getEntity()
    {
        return new Job();
    }
}
