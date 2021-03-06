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
use Maclof\Kubernetes\Models\ReplicationController;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\Image;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\SecretReference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\SecretVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Contracts\Container\PersistentVolumeInterface;
use Teknoo\East\Paas\Contracts\Container\PopulatedVolumeInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberInterface;
use Throwable;

use function array_map;

/**
 * Deployment Transcriber to translate CompiledDeployment's pods and containers to Kubernetes ReplicationsController
 * manifest.
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ReplicationControllerTranscriber implements DeploymentInterface
{
    /**
     * @param array<string, mixed> $specs
     * @param array<string, array<string, Image>>|Image[][] $images
     */
    private static function convertToContainer(array &$specs, Pod $pod, array $images): void
    {
        foreach ($pod as $container) {
            $image = $images[$container->getImage()][$container->getVersion()];
            $spec = [
                'name'  => $container->getName(),
                'image' => $image->getUrl() . ':' . $image->getTag(),
                'imagePullPolicy' => 'Always',
                'ports' => array_map(
                    fn ($port) => ['containerPort' => $port,],
                    $container->getListen()
                )
            ];

            $envsVars = [];
            foreach ($container->getVariables() as $name => &$value) {
                if ($value instanceof SecretReference) {
                    $envsVars[] = [
                        'name' => $name,
                        'valueFrom' => [
                            'secretKeyRef' => [
                                'name' => $value->getName(),
                                'key' => $value->getKey(),
                            ],
                        ],
                    ];

                    continue;
                }

                $envsVars[] = [
                    'name' => $name,
                    'value' => $value
                ];
            }

            if (!empty($envsVars)) {
                $spec['env'] = $envsVars;
            }

            $volumesMount = [];
            foreach ($container->getVolumes() as $volume) {
                $volumesMount[] = [
                    'name' => $volume->getName(),
                    'mountPath' => $volume->getMountPath(),
                    'readOnly' => $volume instanceof PopulatedVolumeInterface,
                ];
            }

            if (!empty($volumesMount)) {
                $spec['volumeMounts'] = $volumesMount;
            }

            $specs['spec']['template']['spec']['containers'][] = $spec;
        }
    }

    /**
     * @param array<string, mixed> $specs
     * @param array<string, SecretVolume|Volume> $volumes
     */
    private static function convertToVolumes(array &$specs, array $volumes): void
    {
        foreach ($volumes as $volume) {
            if ($volume instanceof PersistentVolumeInterface) {
                $specs['spec']['template']['spec']['volumes'][] = [
                    'name' => $volume->getName(),
                    'persistentVolumeClaim' => [
                        'claimName' => $volume->getStorageIdentifier(),
                    ],
                ];

                continue;
            }

            if ($volume instanceof SecretVolume) {
                $specs['spec']['template']['spec']['volumes'][] = [
                    'name' => $volume->getName(),
                    'secret' => [
                        'secretName' => $volume->getSecretIdentifier(),
                    ],
                ];

                continue;
            }

            $specs['spec']['template']['spec']['initContainers'][] = [
                'name' => $volume->getName(),
                'image' => $volume->getUrl(),
                'imagePullPolicy' => 'Always',
                'volumeMounts' => [
                    [
                        'name' => $volume->getName(),
                        'mountPath' => $volume->getMountPath(),
                        'readOnly' => false,
                    ]
                ]
            ];

            $specs['spec']['template']['spec']['volumes'][] = [
                'name' => $volume->getName(),
                'emptyDir' => []
            ];
        }
    }

    /**
     * @param array<string, array<string, Image>>|Image[][] $images
     * @param array<string, Volume>|Volume[] $volumes
     */
    private static function convertToReplicationController(
        Pod $pod,
        array $images,
        array $volumes,
        string $namespace
    ): ReplicationController {
        $specs = [
            'metadata' => [
                'name' => $pod->getName() . '-deployment',
                'namespace' => $namespace,
                'labels' => [
                    'name' => $pod->getName(),
                ],
            ],
            'spec' => [
                'replicas' => $pod->getReplicas(),
                'template' => [
                    'metadata' => [
                        'name' => $pod->getName() . '-pod',
                        'namespace' => $namespace,
                        'labels' => [
                            'name' => $pod->getName(),
                        ],
                    ],
                    'spec' => [
                        'containers' => []
                    ],
                ],
            ],
        ];

        self::convertToVolumes($specs, $volumes);
        self::convertToContainer($specs, $pod, $images);

        return new ReplicationController($specs);
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        KubernetesClient $client,
        PromiseInterface $promise
    ): TranscriberInterface {
        $compiledDeployment->foreachPod(
            static function (Pod $pod, array $images, array $volumes, string $namespace) use ($client, $promise) {
                $kubeController = static::convertToReplicationController($pod, $images, $volumes, $namespace);

                try {
                    if (!empty($namespace)) {
                        $client->setNamespace($namespace);
                    }

                    $rcRepository = $client->replicationControllers();
                    if ($rcRepository->exists($kubeController->getMetadata('name'))) {
                        $result = $rcRepository->update($kubeController);
                    } else {
                        $result = $rcRepository->create($kubeController);
                    }

                    $promise->success($result);
                } catch (Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
