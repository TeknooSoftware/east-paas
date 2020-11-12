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

namespace Teknoo\East\Paas\Infrastructures\Doctrine;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ObjectManager;
use Psr\Container\ContainerInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\AccountRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\ClusterRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\JobRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\ProjectRepositoryInterface;
use Teknoo\East\Paas\Infrastructures\Doctrine\Repository\ODM\AccountRepository;
use Teknoo\East\Paas\Infrastructures\Doctrine\Repository\ODM\ClusterRepository;
use Teknoo\East\Paas\Infrastructures\Doctrine\Repository\ODM\JobRepository;
use Teknoo\East\Paas\Infrastructures\Doctrine\Repository\ODM\ProjectRepository;
use Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Account;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Job;
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
