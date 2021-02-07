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

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Maclof\Kubernetes\Client as KubernetesClient;
use Maclof\Kubernetes\Models\Service as KubeService;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;
use Teknoo\East\Paas\Container\Expose\Service;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\ExposingInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ServiceTranscriber implements ExposingInterface
{
    private static function convertToService(Service $service, string $namespace): KubeService
    {
        $ports = [];
        foreach ($service->getPorts() as $listen => $target) {
            $ports[] = [
                'protocol' => $service->getProtocol(),
                'port' => $listen,
                'targetPort' => $target,
            ];
        }

        $type = 'LoadBalancer';
        if ($service->isInternal()) {
            $type = 'ClusterIP';
        }

        $specs = [
            'metadata' => [
                'name' => $service->getName() . '-service',
                'namespace' => $namespace,
                'labels' => [
                    'name' => $service->getName(),
                ],
            ],
            'spec' => [
                'selector' => [
                    'name' => $service->getPodName(),
                ],
                'type' => $type,
                'ports' => $ports,
            ],
        ];

        return new KubeService($specs);
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        KubernetesClient $client,
        PromiseInterface $promise
    ): TranscriberInterface {
        $compiledDeployment->foreachService(
            static function (Service $service, string $namespace) use ($client, $promise) {
                $kubeService = static::convertToService($service, $namespace);

                try {
                    if (!empty($namespace)) {
                        $client->setNamespace($namespace);
                    }

                    $serviceRepository = $client->services();
                    if ($serviceRepository->exists($kubeService->getMetadata('name'))) {
                        $serviceRepository->delete($kubeService);
                    }

                    $result = $serviceRepository->create($kubeService);

                    $promise->success($result);
                } catch (\Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
