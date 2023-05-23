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

use Teknoo\East\Paas\Compilation\CompiledDeployment\UpgradeStrategy;
use Teknoo\Kubernetes\Client as KubernetesClient;
use Teknoo\Kubernetes\Model\StatefulSet;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheckType;
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
 * "Stateful Sets transcriber" to translate CompiledDeployment's pods and containers to Kubernetes ReplicationsSet
 * manifest.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class StatefulSetsTranscriber implements DeploymentInterface
{
    use CommonTrait;

    private const NAME_SUFFIX = '-sfset';
    private const POD_SUFFIX = '-pod';
    private const VOLUME_SUFFIX = '-volume';
    private const SECRET_SUFFIX = '-secret';
    private const MAP_SUFFIX = '-map';

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
                    static fn($port): array => ['containerPort' => $port,],
                    $container->getListen()
                )
            ];

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
        string $requireLabel,
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
                    'spec' => [
                        'hostAliases' => $hostAliases,
                        'containers' => [],
                    ],
                ],
            ],
        ];

        $specs['spec']['strategy'] = match ($pod->getUpgradeStrategy()) {
            UpgradeStrategy::RollingUpgrade => [
                'type' => 'RollingUpdate',
                'rollingUpdate' => [
                    'maxSurge' => $pod->getMaxUpgradingPods(),
                    'maxUnavailable' => $pod->getMaxUnavailablePods(),
                ],
            ],
            UpgradeStrategy::Recreate => [
                'type' => 'OnDelete',
            ],
            UpgradeStrategy::OnDelete => [
                'type' => 'OnDelete',
            ],
        };

        if (!empty($imagePullSecretsName = $pod->getOciRegistryConfigName())) {
            $specs['spec']['template']['spec']['imagePullSecrets'] = [
                ['name' => $imagePullSecretsName,],
            ];
        }

        if (null !== ($fsGroup = $pod->getFsGroup())) {
            $specs['spec']['template']['spec']['securityContext']['fsGroup'] = $fsGroup;
        }

        if (!empty($requires = $pod->getRequires())) {
            $exprs = [];
            foreach ($requires as $require) {
                $exprs[] = [
                    'key' => "$requireLabel/$require",
                    'operator' => 'Exists',
                ];
            }

            $specs['spec']['template']['spec']['affinity']['nodeAffinity'] = [
                'requiredDuringSchedulingIgnoredDuringExecution' => [
                    'nodeSelectorTerms' => [
                        [
                            'matchExpressions' => $exprs,
                        ],
                    ],
                ],
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
    private static function convertToStatefullSets(
        string $name,
        Pod $pod,
        array $images,
        array $volumes,
        string $namespace,
        int $version,
        callable $prefixer,
        string $requireLabel,
    ): StatefulSet {
        return new StatefulSet(
            static::writeSpec(
                $name,
                $pod,
                $images,
                $volumes,
                $namespace,
                $version,
                $prefixer,
                $requireLabel,
            )
        );
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        KubernetesClient $client,
        PromiseInterface $promise
    ): TranscriberInterface {
        $requireLabel = $this->requireLabel;
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
                $requireLabel
            ): void {
                if ($pod->isStateless()) {
                    return;
                }

                $prefixer = self::createPrefixer($prefix);
                try {
                    if (!empty($namespace)) {
                        $client->setNamespace($namespace);
                    }

                    $name = $prefixer($pod->getName());
                    $sfsRepository = $client->statefulsets();

                    $previousStatefulSet = $sfsRepository->setLabelSelector(['name' => $name])->first();
                    $version = 1;
                    if (null !== $previousStatefulSet) {
                        $annotations = $previousStatefulSet->toArray();
                        $oldVersion = (
                            (int) substr(
                                string: ($annotations['metadata']['annotations']['teknoo.east.paas.version'] ?? 'v1'),
                                offset: 1,
                            )
                        );
                        $version = $oldVersion + 1;
                    }
                } catch (Throwable $error) {
                    $promise->fail($error);

                    return;
                }

                $kubeSet = self::convertToStatefullSets(
                    name: $name,
                    pod: $pod,
                    images: $images,
                    volumes: $volumes,
                    namespace: $namespace,
                    version: $version,
                    prefixer: $prefixer,
                    requireLabel:$requireLabel,
                );

                try {
                    $result = $sfsRepository->apply($kubeSet);

                    $result = self::cleanResult($result);

                    if (
                        null !== $previousStatefulSet
                        && UpgradeStrategy::Recreate === $pod->getUpgradeStrategy()
                    ) {
                        //If upgrade strategy is recreate, not natively available in kubernetes, we will delete current
                        //pods
                        $pods = $client->pods();
                        $labelSelector = ['vname' => $name . '-v' . $oldVersion];
                        foreach ($pods->setLabelSelector($labelSelector)->find() as $podModel) {
                            $pods->delete($podModel);
                        }
                    }

                    $promise->success($result);
                } catch (Throwable $throwable) {
                    $promise->fail($throwable);
                }
            }
        );

        return $this;
    }
}
