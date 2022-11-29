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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Maclof\Kubernetes\Client as KubernetesClient;
use Maclof\Kubernetes\Models\Service as KubeService;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Service;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\ExposingInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberInterface;
use Throwable;

use function strtolower;

/**
 * "Exposing transcriber" to translate CompiledDeployment's services to Kubernetes Services manifest.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ServiceTranscriber implements ExposingInterface
{
    /**
     * @return array<string, mixed>
     */
    protected static function writeSpec(
        Service $service,
        string $namespace
    ): array {
        $ports = [];
        foreach ($service->getPorts() as $listen => $target) {
            $ports[] = [
                'name' => strtolower($service->getName() . '-' . $listen),
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
                'name' => $service->getName(),
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

        return $specs;
    }
    private static function convertToService(Service $service, string $namespace): KubeService
    {
        return new KubeService(
            static::writeSpec($service, $namespace)
        );
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        KubernetesClient $client,
        PromiseInterface $promise
    ): TranscriberInterface {
        $compiledDeployment->foreachService(
            static function (Service $service, string $namespace) use ($client, $promise) {
                $kubeService = self::convertToService($service, $namespace);

                try {
                    if (!empty($namespace)) {
                        $client->setNamespace($namespace);
                    }

                    $serviceRepository = $client->services();
                    $name = $kubeService->getMetadata('name') ?? $service->getName();
                    if ($serviceRepository->exists($name)) {
                        $serviceRepository->delete($kubeService);
                    }

                    $result = $serviceRepository->create($kubeService);

                    $promise->success($result);
                } catch (Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
