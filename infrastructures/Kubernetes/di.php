<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Kubernetes;

use DomainException;
use Psr\Container\ContainerInterface;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\DI\Exception\InvalidArgumentException;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\ClientFactoryInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberCollectionInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\IngressTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\NamespaceTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\DeploymentTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\ConfigMapTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\SecretTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\ServiceTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\StatefulSetsTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\VolumeTranscriber;

use function DI\decorate;
use function DI\create;
use function DI\get;
use function is_a;
use function is_array;
use function is_iterable;
use function iterator_to_array;
use function sys_get_temp_dir;

return [
    ClientFactoryInterface::class => function (ContainerInterface $container): ClientFactoryInterface {
        $tempDir = sys_get_temp_dir();
        if ($container->has('teknoo.east.paas.worker.tmp_dir')) {
            $tempDir = $container->get('teknoo.east.paas.worker.tmp_dir');
        }

        $client = null;
        if ($container->has('teknoo.east.paas.kubernetes.http.client')) {
            $client = $container->get('teknoo.east.paas.kubernetes.http.client');
        }

        $sslVerify = true;
        if ($container->has('teknoo.east.paas.kubernetes.ssl.verify')) {
            $sslVerify = (bool) $container->get('teknoo.east.paas.kubernetes.ssl.verify');
        }

        $timeout = null;
        if ($container->has('teknoo.east.paas.kubernetes.timeout')) {
            $timeout = (int) $container->get('teknoo.east.paas.kubernetes.timeout');
        }

        return new Factory($tempDir, $client, $sslVerify, $timeout);
    },

    IngressTranscriber::class . ':class' => IngressTranscriber::class,
    IngressTranscriber::class => static function (ContainerInterface $container): IngressTranscriber {
        $defaultIngressClass = null;
        $defaultServiceName = null;
        $defaultServicePort = null;
        $defaultAnnotations = [];

        if ($container->has('teknoo.east.paas.kubernetes.ingress.default_ingress_class')) {
            $defaultIngressClass = (string) $container->get(
                'teknoo.east.paas.kubernetes.ingress.default_ingress_class'
            );
        }

        if ($container->has('teknoo.east.paas.kubernetes.ingress.default_service.name')) {
            $defaultServiceName = (string) $container->get('teknoo.east.paas.kubernetes.ingress.default_service.name');
        }

        if ($container->has('teknoo.east.paas.kubernetes.ingress.default_service.port')) {
            $defaultServicePort = (int) $container->get('teknoo.east.paas.kubernetes.ingress.default_service.port');
        }

        if ($container->has('teknoo.east.paas.kubernetes.ingress.default_annotations')) {
            $defaultAnnotations = $container->get('teknoo.east.paas.kubernetes.ingress.default_annotations');
        }

        if (!is_iterable($defaultAnnotations) && !is_array($defaultAnnotations)) {
            throw new InvalidArgumentException(
                "`teknoo.east.paas.kubernetes.ingress.default_annotations` must be an array or iterable"
            );
        }

        if (!is_array($defaultAnnotations)) {
            $defaultAnnotations = iterator_to_array(
                iterator: $defaultAnnotations,
                preserve_keys: true,
            );
        }

        $className = $container->get(IngressTranscriber::class . ':class');
        if (!is_a($className, IngressTranscriber::class, true)) {
            throw new DomainException("The class $className is not a ingress transcriber");
        }

        return new $className(
            defaultIngressClass: $defaultIngressClass,
            defaultIngressService: $defaultServiceName,
            defaultIngressPort: $defaultServicePort,
            defaultIngressAnnotations: $defaultAnnotations,
        );
    },

    NamespaceTranscriber::class . ':class' => NamespaceTranscriber::class,
    NamespaceTranscriber::class => static function (ContainerInterface $container): NamespaceTranscriber {
        $className = $container->get(NamespaceTranscriber::class . ':class');
        if (!is_a($className, NamespaceTranscriber::class, true)) {
            throw new DomainException("The class $className is not a namespace transcriber");
        }

        return new $className();
    },

    DeploymentTranscriber::class . ':class' => DeploymentTranscriber::class,
    DeploymentTranscriber::class => static function (
        ContainerInterface $container
    ): DeploymentTranscriber {
        $className = $container->get(DeploymentTranscriber::class . ':class');
        if (!is_a($className, DeploymentTranscriber::class, true)) {
            throw new DomainException("The class $className is not a deployment transcriber");
        }

        $requireLabel = 'paas.east.teknoo.net';
        if ($container->has('teknoo.east.paas.kubernetes.deployment.require_label')) {
            $requireLabel = (string) $container->get('teknoo.east.paas.kubernetes.deployment.require_label');
        }

        return new $className($requireLabel);
    },

    StatefulSetsTranscriber::class . ':class' => StatefulSetsTranscriber::class,
    StatefulSetsTranscriber::class => static function (
        ContainerInterface $container
    ): StatefulSetsTranscriber {
        $className = $container->get(StatefulSetsTranscriber::class . ':class');
        if (!is_a($className, StatefulSetsTranscriber::class, true)) {
            throw new DomainException("The class $className is not a deployment transcriber");
        }

        $requireLabel = 'paas.east.teknoo.net';
        if ($container->has('teknoo.east.paas.kubernetes.statefulSets.require_label')) {
            $requireLabel = (string) $container->get('teknoo.east.paas.kubernetes.statefulSets.require_label');
        }

        return new $className($requireLabel);
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

    ServiceTranscriber::class . ':class' => ServiceTranscriber::class,
    ServiceTranscriber::class => static function (ContainerInterface $container): ServiceTranscriber {
        $className = $container->get(ServiceTranscriber::class . ':class');
        if (!is_a($className, ServiceTranscriber::class, true)) {
            throw new DomainException("The class $className is not a service transcriber");
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

    TranscriberCollectionInterface::class => get(TranscriberCollection::class),

    TranscriberCollection::class => static function (ContainerInterface $container): TranscriberCollection {
        $collection = new TranscriberCollection();
        $collection->add(5, $container->get(NamespaceTranscriber::class));
        $collection->add(10, $container->get(SecretTranscriber::class));
        $collection->add(10, $container->get(ConfigMapTranscriber::class));
        $collection->add(10, $container->get(VolumeTranscriber::class));
        $collection->add(30, $container->get(DeploymentTranscriber::class));
        $collection->add(31, $container->get(StatefulSetsTranscriber::class));
        $collection->add(40, $container->get(ServiceTranscriber::class));
        $collection->add(50, $container->get(IngressTranscriber::class));

        return $collection;
    },

    Driver::class => create()
        ->constructor(
            get(ClientFactoryInterface::class),
            get(TranscriberCollectionInterface::class)
        ),

    Directory::class => decorate(static function (Directory $previous, ContainerInterface $container) {
        $previous->register('kubernetes', $container->get(Driver::class));

        return $previous;
    }),
];
