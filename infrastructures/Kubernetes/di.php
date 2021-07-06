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

namespace Teknoo\East\Paas\Infrastructures\Kubernetes;

use Psr\Container\ContainerInterface;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\ClientFactoryInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberCollectionInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\IngressTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\ReplicationControllerTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\SecretTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\ServiceTranscriber;

use function DI\decorate;
use function DI\create;
use function DI\get;
use function sys_get_temp_dir;

return [
    ClientFactoryInterface::class => function (ContainerInterface $container): ClientFactoryInterface {
        $tempDir = sys_get_temp_dir();
        if ($container->has('teknoo.east.paas.worker.tmp_dir')) {
            $tempDir = $container->get('teknoo.east.paas.worker.tmp_dir');
        }

        $verify = true;
        if ($container->has('teknoo.east.paas.kubernetes.ssl.verify')) {
            $verify = (bool) $container->get('teknoo.east.paas.kubernetes.ssl.verify');
        }

        return new Factory($tempDir, $verify);
    },

    IngressTranscriber::class => static function (ContainerInterface $container): IngressTranscriber {
        $defaultIngressClass = null;
        $defaultServiceName = null;
        $defaultServicePort = null;

        if ($container->has('teknoo.east.paas.kubernetes.default_ingress_class')) {
            $defaultIngressClass = (string) $container->get('teknoo.east.paas.kubernetes.default_ingress_class');
        }

        if ($container->has('teknoo.east.paas.kubernetes.default_service.name')) {
            $defaultServiceName = (string) $container->get('teknoo.east.paas.kubernetes.default_service.name');
        }

        if ($container->has('teknoo.east.paas.kubernetes.default_service.port')) {
            $defaultServicePort = (int) $container->get('teknoo.east.paas.kubernetes.default_service.port');
        }

        return new IngressTranscriber(
            $defaultIngressClass,
            $defaultServiceName,
            $defaultServicePort
        );
    },
    ReplicationControllerTranscriber::class => create(),
    SecretTranscriber::class => create(),
    ServiceTranscriber::class => create(),

    TranscriberCollectionInterface::class => get(TranscriberCollection::class),
    TranscriberCollection::class => static function (ContainerInterface $container): TranscriberCollection {
        $collection = new TranscriberCollection();
        $collection->add(10, $container->get(SecretTranscriber::class));
        $collection->add(20, $container->get(ReplicationControllerTranscriber::class));
        $collection->add(30, $container->get(ServiceTranscriber::class));
        $collection->add(40, $container->get(IngressTranscriber::class));

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
