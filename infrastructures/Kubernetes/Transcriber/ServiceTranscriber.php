<?php

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

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Teknoo\Kubernetes\Client as KubernetesClient;
use Teknoo\Kubernetes\Model\Service as KubeService;
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
    use CommonTrait;

    /**
     * @return array<string, mixed>
     */
    protected static function writeSpec(
        Service $service,
        string $namespace,
        callable $prefixer,
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
                'name' => $prefixer($service->getName()),
                'namespace' => $namespace,
                'labels' => [
                    'name' => $prefixer($service->getName()),
                ],
            ],
            'spec' => [
                'selector' => [
                    'name' => $prefixer($service->getPodName()),
                ],
                'type' => $type,
                'ports' => $ports,
            ],
        ];

        return $specs;
    }

    private static function convertToService(Service $service, string $namespace, callable $prefixer): KubeService
    {
        return new KubeService(
            static::writeSpec($service, $namespace, $prefixer)
        );
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        KubernetesClient $client,
        PromiseInterface $promise
    ): TranscriberInterface {
        $compiledDeployment->foreachService(
            static function (Service $service, string $namespace, string $prefix,) use ($client, $promise): void {
                $prefixer = self::createPrefixer($prefix);
                $kubeService = self::convertToService($service, $namespace, $prefixer);

                try {
                    if (!empty($namespace)) {
                        $client->setNamespace($namespace);
                    }

                    $serviceRepository = $client->services();
                    $name = $kubeService->getMetadata('name') ?? $prefixer($service->getName());
                    if ($serviceRepository->exists($name)) {
                        $serviceRepository->delete($kubeService);
                    }

                    $result = $serviceRepository->apply($kubeService);

                    $result = self::cleanResult($result);

                    $promise->success($result);
                } catch (Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
