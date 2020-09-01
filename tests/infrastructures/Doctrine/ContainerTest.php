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

namespace Teknoo\Tests\East\Paas\Infrastructures\Doctrine;

use DI\Container;
use DI\ContainerBuilder;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Contracts\DbSource\Repository\AccountRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\BillingInformationRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\ClusterRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\JobRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\PaymentInformationRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\ProjectRepositoryInterface;
use Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Account;
use Teknoo\East\Paas\Object\BillingInformation;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Job;
use Teknoo\East\Paas\Object\PaymentInformation;
use Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Project;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ContainerTest extends TestCase
{
    /**
     * @return Container
     * @throws \Exception
     */
    protected function buildContainer() : Container
    {
        $containerDefinition = new ContainerBuilder();
        $containerDefinition->addDefinitions(__DIR__.'/../../../infrastructures/Doctrine/di.php');

        return $containerDefinition->build();
    }

    private function generateTestForRepository(string $objectClass, string $repositoryClass)
    {
        $container = $this->buildContainer();
        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects(self::any())->method('getRepository')->with($objectClass)->willReturn(
            $this->createMock(DocumentRepository::class)
        );

        $container->set(ObjectManager::class, $objectManager);
        $repository = $container->get($repositoryClass);

        self::assertInstanceOf(
            $repositoryClass,
            $repository
        );
    }

    private function generateTestForRepositoryWithUnsupportedRepository(string $objectClass, string $repositoryClass)
    {
        $container = $this->buildContainer();
        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects(self::any())->method('getRepository')->with($objectClass)->willReturn(
            $this->createMock(\DateTime::class)
        );

        $container->set(ObjectManager::class, $objectManager);
        $container->get($repositoryClass);
    }

    public function testAccountRepository()
    {
        $this->generateTestForRepository(Account::class, AccountRepositoryInterface::class);
    }

    public function testBillingInformationRepository()
    {
        $this->generateTestForRepository(BillingInformation::class, BillingInformationRepositoryInterface::class);
    }

    public function testPaymentInformationRepository()
    {
        $this->generateTestForRepository(PaymentInformation::class, PaymentInformationRepositoryInterface::class);
    }

    public function testProjectRepository()
    {
        $this->generateTestForRepository(Project::class, ProjectRepositoryInterface::class);
    }

    public function testJobRepository()
    {
        $this->generateTestForRepository(Job::class, JobRepositoryInterface::class);
    }

    public function testClusterRepository()
    {
        $this->generateTestForRepository(Cluster::class, ClusterRepositoryInterface::class);
    }

    public function testAccountRepositoryWithUnsupportedRepository()
    {
        $this->expectException(\RuntimeException::class);
        $this->generateTestForRepositoryWithUnsupportedRepository(Account::class, AccountRepositoryInterface::class);
    }

    public function testBillingInformationRepositoryWithUnsupportedRepository()
    {
        $this->expectException(\RuntimeException::class);
        $this->generateTestForRepositoryWithUnsupportedRepository(BillingInformation::class, BillingInformationRepositoryInterface::class);
    }

    public function testPaymentInformationRepositoryWithUnsupportedRepository()
    {
        $this->expectException(\RuntimeException::class);
        $this->generateTestForRepositoryWithUnsupportedRepository(PaymentInformation::class, PaymentInformationRepositoryInterface::class);
    }

    public function testProjectRepositoryWithUnsupportedRepository()
    {
        $this->expectException(\RuntimeException::class);
        $this->generateTestForRepositoryWithUnsupportedRepository(Project::class, ProjectRepositoryInterface::class);
    }

    public function testJobRepositoryWithUnsupportedRepository()
    {
        $this->expectException(\RuntimeException::class);
        $this->generateTestForRepositoryWithUnsupportedRepository(Job::class, JobRepositoryInterface::class);
    }

    public function testClusterRepositoryWithUnsupportedRepository()
    {
        $this->expectException(\RuntimeException::class);
        $this->generateTestForRepositoryWithUnsupportedRepository(Cluster::class, ClusterRepositoryInterface::class);
    }
}