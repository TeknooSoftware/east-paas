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
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\Contracts\DBSource\RepositoryInterface;
use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\ProjectRepositoryInterface;
use Teknoo\East\Paas\Loader\ProjectLoader;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Paas\Object\Project;
use Teknoo\States\Proxy\Exception\StateNotFound;
use Teknoo\Tests\East\Common\Loader\LoaderTestTrait;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ProjectLoader::class)]
class ProjectLoaderTest extends TestCase
{
    use LoaderTestTrait;

    private (RepositoryInterface&Stub)|(RepositoryInterface&MockObject)|null $repository = null;

    public function getRepositoryMock(bool $stub = false): (RepositoryInterface&Stub)|(RepositoryInterface&MockObject)
    {
        if (!$this->repository instanceof RepositoryInterface) {
            if ($stub) {
                $this->repository = $this->createStub(ProjectRepositoryInterface::class);
            } else {
                $this->repository = $this->createMock(ProjectRepositoryInterface::class);
            }
        }

        return $this->repository;
    }

    public function buildLoader(): LoaderInterface&ProjectLoader
    {
        $repository = $this->getRepositoryMock(true);
        return new ProjectLoader($repository);
    }

    public function buildLoaderWithBadCollectionImplementation(): LoaderInterface&ProjectLoader
    {
        $repository = $this->getRepositoryMock(true);
        return new class ($repository) extends ProjectLoader {
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

    public function buildLoaderWithNotCollectionImplemented(): LoaderInterface&ProjectLoader
    {
        $repository = $this->getRepositoryMock(true);
        return new ProjectLoader($repository);
    }

    /**
     * @return Project
     * @throws StateNotFound
     */
    public function getEntity()
    {
        return new Project(new Account());
    }
}
