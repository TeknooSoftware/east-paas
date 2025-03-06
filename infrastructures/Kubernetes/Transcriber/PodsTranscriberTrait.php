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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Teknoo\East\Paas\Compilation\CompiledDeployment\Container;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheckType;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\Image;
use Teknoo\East\Paas\Compilation\CompiledDeployment\MapReference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Resource;
use Teknoo\East\Paas\Compilation\CompiledDeployment\SecretReference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\Reference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\MapVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\SecretVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\PersistentVolumeInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\PopulatedVolumeInterface;
use Teknoo\Kubernetes\Model\CronJob;
use Teknoo\Kubernetes\Model\Deployment;
use Teknoo\Kubernetes\Model\Job;
use Teknoo\Kubernetes\Model\Model;
use Teknoo\Kubernetes\Model\StatefulSet;
use Teknoo\Kubernetes\Repository\Repository;

use function array_map;
use function substr;

/**
 * Trait to factorise pods' features transcribing
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
trait PodsTranscriberTrait
{
    public function __construct(
        private readonly string $requireLabel = 'paas.east.teknoo.net'
    ) {
    }

    /**
     * @param array<string, mixed> $spec
     * @param array<string, SecretReference|MapReference|string> $variables
     */
    private static function convertVariables(array &$spec, array $variables, callable $prefixer): void
    {
        $envsVars = [];
        foreach ($variables as $name => &$value) {
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
    }

    /**
     * @param array<string, > $specs
     * @param array<string, array<string, Image>>|Image[][] $images
     */
    private static function convertToContainer(array &$specs, Pod $pod, array $images, callable $prefixer): void
    {
        /** @var Container $container */
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
            ];

            if (!empty($portsListened = $container->getListen())) {
                $spec['ports'] = array_map(
                    static fn($port): array => ['containerPort' => $port,],
                    $portsListened,
                );
            }

            self::convertVariables($spec, $container->getVariables(), $prefixer);

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

            if (null !== ($hc = $container->getHealthCheck())) {
                $spec['livenessProbe'] = match ($hc->getType()) {
                    HealthCheckType::Command => [
                        'initialDelaySeconds' => $hc->getInitialDelay(),
                        'periodSeconds' => $hc->getPeriod(),
                        'exec' => [
                            'command' => $hc->getCommand(),
                        ],
                        'successThreshold' => $hc->getSuccessThreshold(),
                        'failureThreshold' => $hc->getFailureThreshold(),
                    ],
                    HealthCheckType::Tcp => [
                        'initialDelaySeconds' => $hc->getInitialDelay(),
                        'periodSeconds' => $hc->getPeriod(),
                        'tcpSocket' => [
                            'port' => $hc->getPort(),
                        ],
                        'successThreshold' => $hc->getSuccessThreshold(),
                        'failureThreshold' => $hc->getFailureThreshold(),
                    ],
                    HealthCheckType::Http => [
                        'initialDelaySeconds' => $hc->getInitialDelay(),
                        'periodSeconds' => $hc->getPeriod(),
                        'httpGet' => [
                            'path' => $hc->getPath(),
                            'port' => $hc->getPort(),
                            'scheme' => $hc->isSecure() ? 'HTTPS' : 'HTTP',
                        ],
                        'successThreshold' => $hc->getSuccessThreshold(),
                        'failureThreshold' => $hc->getFailureThreshold(),
                    ],
                };
            }

            $resourcesReqs = [];
            /** @var Resource $resource */
            foreach ($container->getResources() as $resource) {
                $resourcesReqs['requests'][$resource->getType()] = $resource->getRequire();
                $resourcesReqs['limits'][$resource->getType()] = $resource->getLimit();
            }

            if (!empty($resourcesReqs)) {
                $spec['resources'] = $resourcesReqs;
            }

            $specs['containers'][] = $spec;
        }
    }

    /**
     * @param array<string, mixed> $specs
     * @param array<string, SecretVolume|MapVolume|Volume> $volumes
     */
    private static function convertToVolumes(array &$specs, Pod $pod, array $volumes, callable $prefixer,): void
    {
        foreach ($volumes as $volume) {
            if ($volume instanceof PersistentVolumeInterface) {
                $specs['volumes'][] = [
                    'name' => $volume->getName() . self::VOLUME_SUFFIX,
                    'persistentVolumeClaim' => [
                        'claimName' => $prefixer($volume->getName()),
                    ],
                ];

                continue;
            }

            if ($volume instanceof SecretVolume) {
                $specs['volumes'][] = [
                    'name' => $volume->getName() . self::VOLUME_SUFFIX,
                    'secret' => [
                        'secretName' => $prefixer($volume->getSecretIdentifier() . self::SECRET_SUFFIX),
                    ],
                ];

                continue;
            }

            if ($volume instanceof MapVolume) {
                $specs['volumes'][] = [
                    'name' => $volume->getName() . self::VOLUME_SUFFIX,
                    'configMap' => [
                        'name' => $prefixer($volume->getMapIdentifier() . self::MAP_SUFFIX),
                    ],
                ];

                continue;
            }

            $resourcesReqs = [];
            foreach ($pod as $container) {
                /** @var Resource $resource */
                foreach ($container->getResources() as $resource) {
                    $resourcesReqs['requests'][$resource->getType()] = $resource->getRequire();
                    $resourcesReqs['limits'][$resource->getType()] = $resource->getLimit();
                }

                if (!empty($resourcesReqs)) {
                    break;
                }
            }

            $initContainerSpec = [
                'name' => $volume->getName(),
                'image' => $volume->getUrl(),
                'imagePullPolicy' => 'Always',
                'volumeMounts' => [
                    [
                        'name' => $volume->getName() . self::VOLUME_SUFFIX,
                        'mountPath' => $volume->getMountPath(),
                        'readOnly' => false,
                    ]
                ],
                'env' => [
                    [
                        'name' => 'MOUNT_PATH',
                        'value' => $volume->getMountPath(),
                    ]
                ]
            ];

            if (!empty($resourcesReqs)) {
                $initContainerSpec['resources'] = $resourcesReqs;
            }

            $specs['initContainers'][] = $initContainerSpec;

            $specs['volumes'][] = [
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
    protected static function podTemplateSpecWriting(
        Pod $pod,
        array $images,
        array $volumes,
        callable $prefixer,
        string $requireLabel,
        DefaultsBag $defaultsBag,
    ): array {
        $hostAlias = [
            'hostnames' => [],
            'ip' => '127.0.0.1',
        ];
        foreach ($pod as $container) {
            $hostAlias['hostnames'][] = $container->getName();
        }

        $hostAliases = [$hostAlias];

        $spec = [
            'hostAliases' => $hostAliases,
            'containers' => [],
        ];

        $imagePullSecretsName = $pod->getOciRegistryConfigName();
        if ($imagePullSecretsName instanceof Reference) {
            $imagePullSecretsName = $defaultsBag->resolve($imagePullSecretsName);
        }

        if (!empty($imagePullSecretsName)) {
            $spec['imagePullSecrets'] = [
                [
                    'name' => $imagePullSecretsName,
                ],
            ];
        }

        if (null !== ($fsGroup = $pod->getFsGroup())) {
            $spec['securityContext']['fsGroup'] = $fsGroup;
        }

        if (!empty($requires = $pod->getRequires())) {
            $exprs = [];
            foreach ($requires as $require) {
                $exprs[] = [
                    'key' => "$requireLabel/$require",
                    'operator' => 'Exists',
                ];
            }

            $spec['affinity']['nodeAffinity'] = [
                'requiredDuringSchedulingIgnoredDuringExecution' => [
                    'nodeSelectorTerms' => [
                        [
                            'matchExpressions' => $exprs,
                        ],
                    ],
                ],
            ];
        }

        self::convertToVolumes($spec, $pod, $volumes, $prefixer);
        self::convertToContainer($spec, $pod, $images, $prefixer);

        return $spec;
    }

    /**
     * @param array<string, array<string, Image>>|Image[][] $images
     * @param array<string, Volume>|Volume[] $volumes
     * @return array<string, mixed>
     */
    protected static function commonSpecWriting(
        string $name,
        Pod $pod,
        array $images,
        array $volumes,
        string $namespace,
        int $version,
        callable $prefixer,
        string $requireLabel,
        callable $updateStrategy,
        bool $addServiceName,
        DefaultsBag $defaultsBag,
    ): array {
        $specs = [
            'metadata' => [
                'name' => $name . self::NAME_SUFFIX,
                'namespace' => $namespace,
                'labels' => [
                    'name' => $prefixer($pod->getName()),
                ],
                'annotations' => [
                    'teknoo.east.paas.version' => 'v' . $version,
                ],
            ],
            'spec' => [
                'replicas' => $pod->getReplicas(),
                'serviceName' => $prefixer($pod->getName()),
                'strategy' => [],
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
                    'spec' => self::podTemplateSpecWriting(
                        pod: $pod,
                        images: $images,
                        volumes: $volumes,
                        prefixer: $prefixer,
                        requireLabel: $requireLabel,
                        defaultsBag: $defaultsBag,
                    ),
                ],
            ],
        ];

        if (false === $addServiceName) {
            unset($specs['spec']['serviceName']);
        }

        $specs['spec']['strategy'] = $updateStrategy();

        return $specs;
    }

    /**
     * @param Repository<Deployment|StatefulSet|Job|CronJob> $repository
     */
    private static function getVersion(
        string $name,
        Repository $repository,
        ?int &$oldVersion,
        ?Model &$previousManifest
    ): int {
        $previousManifest = $repository->setLabelSelector(['name' => $name])->first();

        $version = 1;
        if (null !== $previousManifest) {
            $annotations = $previousManifest->toArray();
            $oldVersion = (
                (int) substr(
                    string: ($annotations['metadata']['annotations']['teknoo.east.paas.version'] ?? 'v1'),
                    offset: 1,
                )
            );
            $version = $oldVersion + 1;
        }

        return $version;
    }
}
