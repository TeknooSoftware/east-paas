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

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriver;

use Maclof\Kubernetes\Models\Ingress as KubeIngress;
use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Container\Expose\Ingress;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
trait IngressTrait
{
    private static function convertToIngress(
        Ingress $ingress,
        ?string $defaultIngressClass,
        ?string $serviceName,
        ?int $servicePort
    ): KubeIngress {
        $rule = [
            'host' => $ingress->getHost(),
        ];

        foreach ($ingress->getPaths() as $path) {
            $rule['http']['paths'][] = [
                'path' => $path->getPath(),
                'pathType' => 'Prefix',
                'backend' => [
                    'serviceName' => $path->getServiceName(),
                    'servicePort' => $path->getServicePort(),
                ]
            ];
        }

        if (!empty($ingress->getDefaultServiceName())) {
            $rule['http']['paths'][] = [
                'path' => '/',
                'pathType' => 'Prefix',
                'backend' => [
                    'serviceName' => $ingress->getDefaultServiceName(),
                    'servicePort' => $ingress->getDefaultServicePort(),
                ]
            ];
        }

        $specs = [
            'metadata' => [
                'name' => $ingress->getName(),
                'labels' => [
                    'name' => $ingress->getName(),
                ],
            ],
            'spec' => [
                'rules' => [$rule],
            ],
        ];

        if (null !== $ingress->getProvider() || null !== $defaultIngressClass) {
            $specs['spec']['ingressClassName'] = $ingress->getProvider() ?? $defaultIngressClass;
        }

        if (null !== $serviceName && null !== $servicePort) {
            $specs['spec']['defaultBackend'] = [
                'serviceName' => $serviceName,
                'servicePort' => $servicePort,
            ];
        }

        if (!empty($ingress->getTlsSecret())) {
            $specs['spec']['tls'][] = [
                'hosts' => [$ingress->getHost()],
                'secretName' => $ingress->getTlsSecret(),
            ];
        }

        return new KubeIngress($specs);
    }

    private function foreachIngress(
        CompiledDeployment $deployment,
        ?string $ingressClass,
        ?string $serviceName,
        ?int $servicePort,
        callable $callback
    ): void {
        $deployment->foreachIngress(
            static function (Ingress $ingress) use ($callback, $ingressClass, $serviceName, $servicePort) {
                $callback(static::convertToIngress($ingress, $ingressClass, $serviceName, $servicePort));
            }
        );
    }
}
