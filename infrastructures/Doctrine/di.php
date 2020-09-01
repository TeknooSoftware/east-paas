<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Doctrine;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ObjectManager;
use Psr\Container\ContainerInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\AccountRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\BillingInformationRepositoryInterface as BillingInfoRepositoryInt;
use Teknoo\East\Paas\Contracts\DbSource\Repository\ClusterRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\JobRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\PaymentInformationRepositoryInterface as PaymentInfoRepositoryInt;
use Teknoo\East\Paas\Contracts\DbSource\Repository\ProjectRepositoryInterface;
use Teknoo\East\Paas\Infrastructures\Doctrine\Repository\ODM\AccountRepository;
use Teknoo\East\Paas\Infrastructures\Doctrine\Repository\ODM\BillingInformationRepository;
use Teknoo\East\Paas\Infrastructures\Doctrine\Repository\ODM\ClusterRepository;
use Teknoo\East\Paas\Infrastructures\Doctrine\Repository\ODM\JobRepository;
use Teknoo\East\Paas\Infrastructures\Doctrine\Repository\ODM\PaymentInformationRepository;
use Teknoo\East\Paas\Infrastructures\Doctrine\Repository\ODM\ProjectRepository;
use Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Account;
use Teknoo\East\Paas\Object\BillingInformation;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Job;
use Teknoo\East\Paas\Object\PaymentInformation;
use Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Project;

use function DI\get;

return [
    AccountRepositoryInterface::class => get(AccountRepository::class),
    AccountRepository::class => static function (ContainerInterface $container): AccountRepositoryInterface {
        $repository = $container->get(ObjectManager::class)->getRepository(Account::class);
        if ($repository instanceof DocumentRepository) {
            return new AccountRepository($repository);
        }

        throw new \RuntimeException(sprintf(
            "Error, repository of class %s are not currently managed",
            \get_class($repository)
        ));
    },

    BillingInfoRepositoryInt::class => get(BillingInformationRepository::class),
    BillingInformationRepository::class => static function (ContainerInterface $container): BillingInfoRepositoryInt {
        $repository = $container->get(ObjectManager::class)->getRepository(BillingInformation::class);
        if ($repository instanceof DocumentRepository) {
            return new BillingInformationRepository($repository);
        }

        throw new \RuntimeException(sprintf(
            "Error, repository of class %s are not currently managed",
            \get_class($repository)
        ));
    },

    JobRepositoryInterface::class => get(JobRepository::class),
    JobRepository::class => static function (ContainerInterface $container): JobRepositoryInterface {
        $repository = $container->get(ObjectManager::class)->getRepository(Job::class);
        if ($repository instanceof DocumentRepository) {
            return new JobRepository($repository);
        }

        throw new \RuntimeException(sprintf(
            "Error, repository of class %s are not currently managed",
            \get_class($repository)
        ));
    },

    PaymentInfoRepositoryInt::class => get(PaymentInformationRepository::class),
    PaymentInformationRepository::class => static function (ContainerInterface $container): PaymentInfoRepositoryInt {
        $repository = $container->get(ObjectManager::class)->getRepository(PaymentInformation::class);
        if ($repository instanceof DocumentRepository) {
            return new PaymentInformationRepository($repository);
        }

        throw new \RuntimeException(sprintf(
            "Error, repository of class %s are not currently managed",
            \get_class($repository)
        ));
    },

    ProjectRepositoryInterface::class => get(ProjectRepository::class),
    ProjectRepository::class => static function (ContainerInterface $container): ProjectRepositoryInterface {
        $repository = $container->get(ObjectManager::class)->getRepository(Project::class);
        if ($repository instanceof DocumentRepository) {
            return new ProjectRepository($repository);
        }

        throw new \RuntimeException(sprintf(
            "Error, repository of class %s are not currently managed",
            \get_class($repository)
        ));
    },

    ClusterRepositoryInterface::class => get(ClusterRepository::class),
    ClusterRepository::class => static function (ContainerInterface $container): ClusterRepositoryInterface {
        $repository = $container->get(ObjectManager::class)->getRepository(Cluster::class);
        if ($repository instanceof DocumentRepository) {
            return new ClusterRepository($repository);
        }

        throw new \RuntimeException(sprintf(
            "Error, repository of class %s are not currently managed",
            \get_class($repository)
        ));
    },
];
