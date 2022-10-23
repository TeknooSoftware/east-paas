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
use Maclof\Kubernetes\Models\PersistentVolumeClaim;
use SebastianBergmann\GlobalState\Snapshot;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\PersistentVolumeInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\VolumeInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberInterface;
use Throwable;

use function array_map;

/**
 * "Deployment transcriber" to translate CompiledDeployment's peristant volume to Kubernetes PVC
 * manifest.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class VolumeTranscriber implements DeploymentInterface
{
    /**
     * @return array<string, mixed>
     */
    protected static function writeSpec(
        PersistentVolumeInterface $volume,
        string $namespace
    ): array {
        return [
            'metadata' => [
                'name' => $volume->getName(),
                'namespace' => $namespace,
                'labels' => [
                    'name' => $volume->getName(),
                ],
            ],
            'spec' => [
                'accessModes' => [
                    'ReadWriteOnce'
                ],
                'storageClassName' => $volume->getStorageIdentifier(),
                'resources' => [
                    'requests' => [
                        'storage' => $volume->getStorageSize()
                    ]
                ],
            ],
        ];
    }

    private static function convertToPVC(
        PersistentVolumeInterface $volume,
        string $namespace
    ): PersistentVolumeClaim {
        return new PersistentVolumeClaim(
            self::writeSpec($volume, $namespace)
        );
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        KubernetesClient $client,
        PromiseInterface $promise
    ): TranscriberInterface {
        $compiledDeployment->foreachVolume(
            function (string $name, VolumeInterface $volume, string $namespace) use ($client, $promise) {
                if (!$volume instanceof PersistentVolumeInterface) {
                    return;
                }

                $pvc = self::convertToPVC($volume, $namespace);

                try {
                    if (!empty($namespace)) {
                        $client->setNamespace($namespace);
                    }

                    $pvcRepository = $client->persistentVolumeClaims();
                    $name = $pvc->getMetadata('name') ?? $volume->getName();
                    if ($pvcRepository->exists($name)) {
                        if (!$volume->isResetOnDeployment()) {
                            $promise->success([]);

                            return;
                        }

                        $pvcRepository->delete($pvc);
                    }

                    $result = $pvcRepository->create($pvc);

                    $promise->success($result);
                } catch (Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
