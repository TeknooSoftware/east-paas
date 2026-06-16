<?php

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

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\DockerCompose;

use DomainException;
use Psr\Container\ContainerInterface;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\RunnerFactoryInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\TranscriberCollectionInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Driver as DriverAlias; //To prevent a bug into PHP-DI
use Teknoo\East\Paas\Infrastructures\DockerCompose\RunnerFactory as RunnerFactoryAlias;
use Teknoo\East\Paas\Infrastructures\DockerCompose\TranscriberCollection as TranscriberCollectionAlias;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\ConfigMapTranscriber;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\DeploymentTranscriber;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\JobTranscriber;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\NetworkTranscriber;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\SecretTranscriber;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\VolumeTranscriber;

use function DI\create;
use function DI\decorate;
use function DI\get;
use function is_a;
use function sys_get_temp_dir;

return [
    RunnerFactoryInterface::class => static function (ContainerInterface $container): RunnerFactoryInterface {
        $tmpDir = sys_get_temp_dir();
        if ($container->has('teknoo.east.paas.worker.tmp_dir')) {
            $tmpDir = (string) $container->get('teknoo.east.paas.worker.tmp_dir');
        }

        $playbookBinary = 'ansible-playbook';
        if ($container->has('teknoo.east.paas.docker-compose.ansible.binary')) {
            $playbookBinary = (string) $container->get('teknoo.east.paas.docker-compose.ansible.binary');
        }

        $timeout = null;
        if ($container->has('teknoo.east.paas.docker-compose.timeout')) {
            $timeout = (float) $container->get('teknoo.east.paas.docker-compose.timeout');
        }

        return new RunnerFactoryAlias(
            tmpDir: $tmpDir,
            playbookBinary: $playbookBinary,
            timeout: $timeout,
        );
    },

    NetworkTranscriber::class . ':class' => NetworkTranscriber::class,
    NetworkTranscriber::class => static function (ContainerInterface $container): NetworkTranscriber {
        $className = $container->get(NetworkTranscriber::class . ':class');
        if (!is_a($className, NetworkTranscriber::class, true)) {
            throw new DomainException("The class $className is not a network transcriber");
        }

        $networkDriver = 'bridge';
        if ($container->has('teknoo.east.paas.docker-compose.network.driver')) {
            $networkDriver = (string) $container->get('teknoo.east.paas.docker-compose.network.driver');
        }

        return new $className($networkDriver);
    },

    SecretTranscriber::class . ':class' => SecretTranscriber::class,
    SecretTranscriber::class => static function (ContainerInterface $container): SecretTranscriber {
        $className = $container->get(SecretTranscriber::class . ':class');
        if (!is_a($className, SecretTranscriber::class, true)) {
            throw new DomainException("The class $className is not a secret transcriber");
        }

        return new $className();
    },

    ConfigMapTranscriber::class . ':class' => ConfigMapTranscriber::class,
    ConfigMapTranscriber::class => static function (ContainerInterface $container): ConfigMapTranscriber {
        $className = $container->get(ConfigMapTranscriber::class . ':class');
        if (!is_a($className, ConfigMapTranscriber::class, true)) {
            throw new DomainException("The class $className is not a configMap transcriber");
        }

        return new $className();
    },

    VolumeTranscriber::class . ':class' => VolumeTranscriber::class,
    VolumeTranscriber::class => static function (ContainerInterface $container): VolumeTranscriber {
        $className = $container->get(VolumeTranscriber::class . ':class');
        if (!is_a($className, VolumeTranscriber::class, true)) {
            throw new DomainException("The class $className is not a volume transcriber");
        }

        return new $className();
    },

    DeploymentTranscriber::class . ':class' => DeploymentTranscriber::class,
    DeploymentTranscriber::class => static function (ContainerInterface $container): DeploymentTranscriber {
        $className = $container->get(DeploymentTranscriber::class . ':class');
        if (!is_a($className, DeploymentTranscriber::class, true)) {
            throw new DomainException("The class $className is not a deployment transcriber");
        }

        return new $className();
    },

    JobTranscriber::class . ':class' => JobTranscriber::class,
    JobTranscriber::class => static function (ContainerInterface $container): JobTranscriber {
        $className = $container->get(JobTranscriber::class . ':class');
        if (!is_a($className, JobTranscriber::class, true)) {
            throw new DomainException("The class $className is not a job transcriber");
        }

        return new $className();
    },

    TranscriberCollectionInterface::class => get(TranscriberCollectionAlias::class),

    TranscriberCollectionAlias::class => static function (
        ContainerInterface $container
    ): TranscriberCollectionAlias {
        $collection = new TranscriberCollectionAlias();
        $collection->add(5, $container->get(NetworkTranscriber::class));
        $collection->add(10, $container->get(SecretTranscriber::class));
        $collection->add(10, $container->get(ConfigMapTranscriber::class));
        $collection->add(10, $container->get(VolumeTranscriber::class));
        $collection->add(30, $container->get(DeploymentTranscriber::class));
        $collection->add(32, $container->get(JobTranscriber::class));

        return $collection;
    },

    DriverAlias::class => static function (ContainerInterface $container): DriverAlias {
        $tmpDir = sys_get_temp_dir();
        if ($container->has('teknoo.east.paas.worker.tmp_dir')) {
            $tmpDir = (string) $container->get('teknoo.east.paas.worker.tmp_dir');
        }

        $deployRoot = '/opt/paas';
        if ($container->has('teknoo.east.paas.docker-compose.deploy_root')) {
            $deployRoot = (string) $container->get('teknoo.east.paas.docker-compose.deploy_root');
        }

        $traefikContainer = 'traefik';
        if ($container->has('teknoo.east.paas.docker-compose.traefik.container')) {
            $traefikContainer = (string) $container->get('teknoo.east.paas.docker-compose.traefik.container');
        }

        return new DriverAlias(
            runnerFactory: $container->get(RunnerFactoryInterface::class),
            transcribers: $container->get(TranscriberCollectionInterface::class),
            templates: [
                'deploy' => __DIR__ . '/templates/deploy.yml.template',
                'expose' => __DIR__ . '/templates/expose.yml.template',
            ],
            tmpDir: $tmpDir,
            deployRoot: $deployRoot,
            traefikContainer: $traefikContainer,
        );
    },

    Directory::class => decorate(static function (Directory $previous, ContainerInterface $container): Directory {
        $previous->register('docker-compose', $container->get(DriverAlias::class));

        return $previous;
    }),
];
