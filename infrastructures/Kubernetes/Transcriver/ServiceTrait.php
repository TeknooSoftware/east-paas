<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriver;

use Maclof\Kubernetes\Models\Service as KubeService;
use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Container\Service;

trait ServiceTrait
{
    private static function convertToService(Service $service): KubeService
    {
        $ports = [];
        foreach ($service->getPorts() as $listen => $target) {
            $ports[] = [
                'protocol' => $service->getProtocol(),
                'port' => $listen,
                'targetPort' => $target,
            ];
        }

        $specs = [
            'metadata' => [
                'name' => $service->getName() . '-service',
                'labels' => [
                    'name' => $service->getName(),
                ],
            ],
            'spec' => [
                'selector' => [
                    'name' => $service->getName(),
                ],
                'type' => 'LoadBalancer',
                'ports' => $ports,
            ],
        ];

        return new KubeService($specs);
    }

    private function foreachService(CompiledDeployment $deployment, callable $callback): void
    {
        $deployment->foreachService(static function (Service $service) use ($callback) {
            $callback(static::convertToService($service));
        });
    }
}
