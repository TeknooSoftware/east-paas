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
use Maclof\Kubernetes\Models\ReplicationController;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\Image;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\SecretReference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\SecretVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\PersistentVolumeInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\PopulatedVolumeInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberInterface;
use Throwable;

use function array_map;
use function substr;

/**
 * Deployment Transcriber to translate CompiledDeployment's pods and containers to Kubernetes ReplicationsController
 * manifest.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ReplicationControllerTranscriber implements DeploymentInterface
{
    private const NAME_SUFFIX = '-ctrl';
    private const POD_SUFFIX = '-pod';
    private const VOLUME_SUFFIX = '-volume';
    private const SECRET_VOLUME_SUFFIX = '-secret';

    /**
     * @param array<string, > $specs
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
                                'name' => $value->getName() . self::SECRET_VOLUME_SUFFIX,
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
                    'name' => $volume->getName() . self::VOLUME_SUFFIX,
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
                    'name' => $volume->getName() . self::VOLUME_SUFFIX,
                    'persistentVolumeClaim' => [
                        'claimName' => $volume->getName(),
                    ],
                ];

                continue;
            }

            if ($volume instanceof SecretVolume) {
                $specs['spec']['template']['spec']['volumes'][] = [
                    'name' => $volume->getName() . self::VOLUME_SUFFIX,
                    'secret' => [
                        'secretName' => $volume->getSecretIdentifier() . self::SECRET_VOLUME_SUFFIX,
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
                        'name' => $volume->getName() . self::VOLUME_SUFFIX,
                        'mountPath' => $volume->getMountPath(),
                        'readOnly' => false,
                    ]
                ]
            ];

            $specs['spec']['template']['spec']['volumes'][] = [
                'name' => $volume->getName() . self::VOLUME_SUFFIX,
                'emptyDir' => []
            ];
        }
    }

    /**
     * @param array<string, array<string, Image>>|Image[][] $images
     * @param array<string, Volume>|Volume[] $volumes
     */
    private static function convertToReplicationController(
        string $name,
        Pod $pod,
        array $images,
        array $volumes,
        string $namespace,
        int $version
    ): ReplicationController {
        $specs = [
            'metadata' => [
                'name' => $name . self::NAME_SUFFIX . '-v' . $version,
                'namespace' => $namespace,
                'labels' => [
                    'name' => $pod->getName(),
                ],
                'annotations' => [
                    'teknoo.space.version' => 'v' . $version,
                ],
            ],
            'spec' => [
                'replicas' => $pod->getReplicas(),
                'template' => [
                    'metadata' => [
                        'name' => $pod->getName() . self::POD_SUFFIX,
                        'namespace' => $namespace,
                        'labels' => [
                            'name' => $pod->getName(),
                        ],
                    ],
                    'spec' => [
                        'containers' => [],
                    ],
                ],
            ],
        ];

        if (!empty($imagePullSecretsName = $pod->getOciRegistryConfigName())) {
            $specs['spec']['template']['spec']['imagePullSecrets'] = [
                ['name' => $imagePullSecretsName,],
            ];
        }

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
            static function (Pod $pod, array $images, array $volumes, string $namespace) use ($client, $promise): void {
                try {
                    if (!empty($namespace)) {
                        $client->setNamespace($namespace);
                    }

                    $name = $pod->getName();
                    $rcRepository = $client->replicationControllers();

                    $ctl = $rcRepository->setLabelSelector(['name' => $name])->first();
                    $version = 1;
                    if (null !== $ctl) {
                        $version = (
                            (int) substr(
                                string:($ctl->toArray()['metadata']['annotations']['teknoo.space.version'] ?? 'v1'),
                                offset: 1,
                            )
                        ) + 1;
                    }
                } catch (Throwable $error) {
                    $promise->fail($error);

                    return;
                }

                $kubeController = self::convertToReplicationController(
                    name: $name,
                    pod: $pod,
                    images: $images,
                    volumes: $volumes,
                    namespace: $namespace,
                    version: $version,
                );

                try {
                    $result = $rcRepository->create($kubeController);

                    $promise->success($result);

                    if (null !== $ctl) {
                        $rcRepository->delete($ctl);
                    }
                } catch (Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
