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

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Teknoo\Kubernetes\Client as KubernetesClient;
use Teknoo\Kubernetes\Model\PersistentVolumeClaim;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\PersistentVolumeInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\VolumeInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberInterface;
use Throwable;

/**
 * "Deployment transcriber" to translate CompiledDeployment's peristant volume to Kubernetes PVC
 * manifest.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class VolumeTranscriber implements DeploymentInterface
{
    use CommonTrait;

    /**
     * @return array<string, mixed>
     */
    protected static function writeSpec(
        PersistentVolumeInterface $volume,
        string $namespace,
        callable $prefixer,
    ): array {
        return [
            'metadata' => [
                'name' => $prefixer($volume->getName()),
                'namespace' => $namespace,
                'labels' => [
                    'name' => $prefixer($volume->getName()),
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
        string $namespace,
        callable $prefixer,
    ): PersistentVolumeClaim {
        return new PersistentVolumeClaim(
            static::writeSpec($volume, $namespace, $prefixer)
        );
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        KubernetesClient $client,
        PromiseInterface $promise
    ): TranscriberInterface {
        $compiledDeployment->foreachVolume(
            function (
                string $name,
                VolumeInterface $volume,
                string $namespace,
                string $prefix,
            ) use (
                $client,
                $promise,
            ) {
                $prefixer = self::createPrefixer($prefix);
                if (!$volume instanceof PersistentVolumeInterface) {
                    return;
                }

                $pvc = self::convertToPVC($volume, $namespace, $prefixer);

                try {
                    if (!empty($namespace)) {
                        $client->setNamespace($namespace);
                    }

                    $pvcRepository = $client->persistentVolumeClaims();
                    $prefixedName = $pvc->getMetadata('name') ?? $prefixer($volume->getName());
                    if ($pvcRepository->exists($prefixedName)) {
                        if (!$volume->isResetOnDeployment()) {
                            $promise->success([]);

                            return;
                        }

                        $pvcRepository->delete($pvc);
                    }

                    $result = $pvcRepository->apply($pvc);

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
