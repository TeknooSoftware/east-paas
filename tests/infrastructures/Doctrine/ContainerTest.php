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

namespace Teknoo\Tests\East\Paas\Infrastructures\Doctrine;

use DI\Container;
use DI\ContainerBuilder;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Contracts\DbSource\Repository\AccountRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\ClusterRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\JobRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\ProjectRepositoryInterface;
use Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Account;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Job;
use Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Project;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ContainerTest extends TestCase
{
    /**
     * @throws \Exception
     */
    protected function buildContainer(): Container
    {
        $containerDefinition = new ContainerBuilder();
        $containerDefinition->addDefinitions(__DIR__.'/../../../infrastructures/Doctrine/di.php');

        return $containerDefinition->build();
    }

    private function generateTestForRepository(string $objectClass, string $repositoryClass): void
    {
        $container = $this->buildContainer();
        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->method('getRepository')->with($objectClass)->willReturn(
            $this->createMock(DocumentRepository::class)
        );

        $container->set(ObjectManager::class, $objectManager);
        $repository = $container->get($repositoryClass);

        $this->assertInstanceOf($repositoryClass, $repository);
    }

    private function generateTestForRepositoryWithUnsupportedRepository(string $objectClass, string $repositoryClass): void
    {
        $container = $this->buildContainer();
        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->method('getRepository')->with($objectClass)->willReturn(
            $this->createMock(ObjectRepository::class)
        );

        $container->set(ObjectManager::class, $objectManager);
        $container->get($repositoryClass);
    }

    public function testAccountRepository(): void
    {
        $this->generateTestForRepository(Account::class, AccountRepositoryInterface::class);
    }

    public function testProjectRepository(): void
    {
        $this->generateTestForRepository(Project::class, ProjectRepositoryInterface::class);
    }

    public function testJobRepository(): void
    {
        $this->generateTestForRepository(Job::class, JobRepositoryInterface::class);
    }

    public function testClusterRepository(): void
    {
        $this->generateTestForRepository(Cluster::class, ClusterRepositoryInterface::class);
    }

    public function testAccountRepositoryWithUnsupportedRepository(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->generateTestForRepositoryWithUnsupportedRepository(Account::class, AccountRepositoryInterface::class);
    }

    public function testProjectRepositoryWithUnsupportedRepository(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->generateTestForRepositoryWithUnsupportedRepository(Project::class, ProjectRepositoryInterface::class);
    }

    public function testJobRepositoryWithUnsupportedRepository(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->generateTestForRepositoryWithUnsupportedRepository(Job::class, JobRepositoryInterface::class);
    }

    public function testClusterRepositoryWithUnsupportedRepository(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->generateTestForRepositoryWithUnsupportedRepository(Cluster::class, ClusterRepositoryInterface::class);
    }
}
