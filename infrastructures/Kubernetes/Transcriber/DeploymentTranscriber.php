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
use Maclof\Kubernetes\Models\Deployment;
use Teknoo\East\Paas\Compilation\CompiledDeployment\MapReference;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\Image;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\SecretReference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\MapVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\SecretVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\PersistentVolumeInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\PopulatedVolumeInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberInterface;
use Throwable;

use function array_map;
use function sleep;
use function substr;

/**
 * "Deployment transcriber" to translate CompiledDeployment's pods and containers to Kubernetes ReplicationsSet
 * manifest.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class DeploymentTranscriber implements DeploymentInterface
{
    use CommonTrait;

    private const NAME_SUFFIX = '-dplmt';
    private const POD_SUFFIX = '-pod';
    private const VOLUME_SUFFIX = '-volume';
    private const SECRET_SUFFIX = '-secret';
    private const MAP_SUFFIX = '-map';

    /**
     * @param array<string, > $specs
     * @param array<string, array<string, Image>>|Image[][] $images
     */
    private static function convertToContainer(array &$specs, Pod $pod, array $images, callable $prefixer,): void
    {
        foreach ($pod as $container) {
            if (isset($images[$container->getImage()][$container->getVersion()])) {
                $image = $images[$container->getImage()][$container->getVersion()];
                $imgUrl = $image->getUrl() . ':' . $image->getTag();
            } else {
                $imgUrl = $container->getImage() . ':' . $container->getVersion();
            }

            $spec = [
                'name'  => $container->getName(),
                'image' => $imgUrl,
                'imagePullPolicy' => 'Always',
                'ports' => array_map(
                    fn ($port) => ['containerPort' => $port,],
                    $container->getListen()
                )
            ];

            $envsVars = [];
            foreach ($container->getVariables() as $name => &$value) {
                if ($value instanceof SecretReference) {
                    if ($value->isImportAll()) {
                        $spec['envFrom'][] = [
                            'secretRef' => [
                                'name' => $prefixer($value->getName() . self::SECRET_SUFFIX),
                            ],
                        ];

                        continue;
                    }

                    $envsVars[] = [
                        'name' => $name,
                        'valueFrom' => [
                            'secretKeyRef' => [
                                'name' => $prefixer($value->getName() . self::SECRET_SUFFIX),
                                'key' => $value->getKey(),
                            ],
                        ],
                    ];

                    continue;
                }

                if ($value instanceof MapReference) {
                    if ($value->isImportAll()) {
                        $spec['envFrom'][] = [
                            'configMapRef' => [
                                'name' => $prefixer($value->getName() . self::MAP_SUFFIX),
                            ],
                        ];

                        continue;
                    }

                    $envsVars[] = [
                        'name' => $name,
                        'valueFrom' => [
                            'configMapKeyRef' => [
                                'name' => $prefixer($value->getName() . self::MAP_SUFFIX),
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
     * @param array<string, SecretVolume|MapVolume|Volume> $volumes
     */
    private static function convertToVolumes(array &$specs, array $volumes, callable $prefixer,): void
    {
        foreach ($volumes as $volume) {
            if ($volume instanceof PersistentVolumeInterface) {
                $specs['spec']['template']['spec']['volumes'][] = [
                    'name' => $volume->getName() . self::VOLUME_SUFFIX,
                    'persistentVolumeClaim' => [
                        'claimName' => $prefixer($volume->getName()),
                    ],
                ];

                continue;
            }

            if ($volume instanceof SecretVolume) {
                $specs['spec']['template']['spec']['volumes'][] = [
                    'name' => $volume->getName() . self::VOLUME_SUFFIX,
                    'secret' => [
                        'secretName' => $prefixer($volume->getSecretIdentifier() . self::SECRET_SUFFIX),
                    ],
                ];

                continue;
            }

            if ($volume instanceof MapVolume) {
                $specs['spec']['template']['spec']['volumes'][] = [
                    'name' => $volume->getName() . self::VOLUME_SUFFIX,
                    'configMap' => [
                        'name' => $prefixer($volume->getMapIdentifier() . self::MAP_SUFFIX),
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
     * @return array<string, mixed>
     */
    protected static function writeSpec(
        string $name,
        Pod $pod,
        array $images,
        array $volumes,
        string $namespace,
        int $version,
        callable $prefixer,
    ): array {
        $hostAlias = [
            'hostnames' => [],
            'ip' => '127.0.0.1',
        ];
        foreach ($pod as $container) {
            $hostAlias['hostnames'][] = $container->getName();
        }

        $hostAliases = [$hostAlias];

        $specs = [
            'metadata' => [
                'name' => $name . self::NAME_SUFFIX,
                'namespace' => $namespace,
                'labels' => [
                    'name' => $prefixer($pod->getName()),
                ],
                'annotations' => [
                    'teknoo.space.version' => 'v' . $version,
                ],
            ],
            'spec' => [
                'replicas' => $pod->getReplicas(),
                'strategy' => [
                    'type' => 'RollingUpdate',
                    'rollingUpdate' => [
                        'maxSurge' => $pod->getMaxUpgradingPods(),
                        'maxUnavailable' => $pod->getMaxUnavailablePods(),
                    ],
                ],
                'selector' => [
                    'matchLabels' => [
                        'name' => $name,
                    ],
                ],
                'template' => [
                    'metadata' => [
                        'name' => $prefixer($pod->getName() . self::POD_SUFFIX),
                        'namespace' => $namespace,
                        'labels' => [
                            'name' => $prefixer($pod->getName()),
                            'vname' => $name . '-v' . $version,
                        ],
                    ],
                    'spec' => [
                        'hostAliases' => $hostAliases,
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

        self::convertToVolumes($specs, $volumes, $prefixer);
        self::convertToContainer($specs, $pod, $images, $prefixer);

        return $specs;
    }
    /**
     * @param array<string, array<string, Image>>|Image[][] $images
     * @param array<string, Volume>|Volume[] $volumes
     */
    private static function convertToDeployment(
        string $name,
        Pod $pod,
        array $images,
        array $volumes,
        string $namespace,
        int $version,
        callable $prefixer,
    ): Deployment {
        return new Deployment(
            static::writeSpec(
                $name,
                $pod,
                $images,
                $volumes,
                $namespace,
                $version,
                $prefixer,
            )
        );
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        KubernetesClient $client,
        PromiseInterface $promise
    ): TranscriberInterface {
        $compiledDeployment->foreachPod(
            static function (
                Pod $pod,
                array $images,
                array $volumes,
                string $namespace,
                string $prefix,
            ) use (
                $client,
                $promise,
            ): void {
                $prefixer = self::createPrefixer($prefix);
                try {
                    if (!empty($namespace)) {
                        $client->setNamespace($namespace);
                    }

                    $name = $prefixer($pod->getName());
                    $rcRepository = $client->deployments();

                    $previousDeployment = $rcRepository->setLabelSelector(['name' => $name])->first();
                    $version = 1;
                    if (null !== $previousDeployment) {
                        $annotations = $previousDeployment->toArray();
                        $oldVersion = (
                            (int) substr(
                                string: ($annotations['metadata']['annotations']['teknoo.space.version'] ?? 'v1'),
                                offset: 1,
                            )
                        );
                        $version = $oldVersion + 1;
                    }
                } catch (Throwable $error) {
                    $promise->fail($error);

                    return;
                }

                $kubeSet = self::convertToDeployment(
                    name: $name,
                    pod: $pod,
                    images: $images,
                    volumes: $volumes,
                    namespace: $namespace,
                    version: $version,
                    prefixer: $prefixer,
                );

                try {
                    $result = $rcRepository->apply($kubeSet);

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
