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

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriver;

use Maclof\Kubernetes\Models\Service as KubeService;
use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Container\Service;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
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