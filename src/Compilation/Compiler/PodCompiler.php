<?php

declare(strict_types=1);

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

namespace Teknoo\East\Paas\Compilation\Compiler;

use DomainException;
use InvalidArgumentException;
use SensitiveParameter;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Container;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheck;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheckType;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\EmbeddedVolumeImage;
use Teknoo\East\Paas\Compilation\CompiledDeployment\MapReference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod\RestartPolicy;
use Teknoo\East\Paas\Compilation\CompiledDeployment\ResourceSet;
use Teknoo\East\Paas\Compilation\CompiledDeployment\SecretReference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\UpgradeStrategy;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\MapVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\PersistentVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\SecretVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Compilation\Compiler\Exception\MissingAttributeException;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\VolumeInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompilerInterface;
use Teknoo\East\Paas\Contracts\Compilation\ExtenderInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

use function array_map;
use function array_pop;
use function explode;
use function implode;
use function is_string;
use function trim;

/**
 * Compilation module able to convert `pods` sections in paas.yaml file as Pod instance.
 * The Pod instance will be pushed into the CompiledDeploymentInterface instance.
 * If the pod define also some secrets, embedded volumes or persistent volumes, SecretVolume, EmbeddedVolume and
 * PersistentVolume will be also created and added to the CompiledDeploymentInterface instance and referenced
 * with the Pod instance.
 * This compiler manage also requirements resources for containers
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class PodCompiler implements CompilerInterface, ExtenderInterface
{
    use MergeTrait;

    private const KEY_ADD = 'add';
    private const KEY_COMMAND = 'command';
    private const KEY_CONTAINERS = 'containers';
    private const KEY_EXTENDS = 'extends';
    private const KEY_FAILURE = 'failure';
    private const KEY_FROM = 'from';
    private const KEY_FROM_MAP = 'from-map';
    private const KEY_FROM_MAPS = 'from-maps';
    private const KEY_FROM_SECRET = 'from-secret';
    private const KEY_FROM_SECRETS = 'from-secrets';
    private const KEY_FS_GROUP = 'fs-group';
    private const KEY_HEALTHCHECK = 'healthcheck';
    private const KEY_HTTP = 'http';
    private const KEY_IMAGE = 'image';
    private const KEY_IMPORT_MAPS = 'import-maps';
    private const KEY_IMPORT_SECRETS = 'import-secrets';
    private const KEY_INITIAL_DELAY_SCDS = 'initial-delay-seconds';
    private const KEY_IS_SECURE = 'is-secure';
    private const KEY_LIMIT = 'limit';
    private const KEY_LISTEN = 'listen';
    private const KEY_MAX_UNAVAILABLE_PODS = 'max-unavailable-pods';
    private const KEY_MAX_UPGRADING_PODS = 'max-upgrading-pods';
    private const KEY_MOUNT_PATH = 'mount-path';
    private const KEY_VOLUME_NAME = 'name';
    private const KEY_OCI_REGISTRY_CONFIG_NAME = 'oci-registry-config-name';
    private const KEY_PATH = 'path';
    private const KEY_PERIOD_SCDS = 'period-seconds';
    private const KEY_PERSISTENT = 'persistent';
    private const KEY_PORT = 'port';
    private const KEY_PROBE = 'probe';
    private const KEY_REPLICAS = 'replicas';
    private const KEY_REQUIRE = 'require';
    private const KEY_REQUIRES = 'requires';
    private const KEY_RESET_ON_DEPLOYMENT = 'reset-on-deployment';
    private const KEY_RESOURCES = 'resources';
    private const KEY_SECURITY = 'security';
    private const KEY_STORAGE_IDENTIFIER = 'storage-provider';
    private const KEY_STORAGE_SIZE = 'storage-size';
    private const KEY_STRATEGY = 'strategy';
    private const KEY_SUCCESS = 'success';
    private const KEY_TCP = 'tcp';
    private const KEY_THRESHOLD = 'threshold';
    private const KEY_TYPE = 'type';
    private const KEY_UPGRADE = 'upgrade';
    private const KEY_VARIABLES = 'variables';
    private const KEY_VERSION = 'version';
    private const KEY_VOLUMES = 'volumes';
    private const KEY_WRITABLES = 'writables';
    private const KEY_WRITE_MANY = 'write-many';
    private const KEY_RESTART_POLICY = 'restart-policy';
    private const VALUE_DEFAULT_LOCAL_PATH_IN_VOLUME = '/volume';
    private const VALUE_LATEST = 'latest';

    /**
     * @param array<string, array<string, mixed>> $podsLibrary
     * @param array<string, array<string, mixed>> $containersLibrary
     */
    public function __construct(
        private readonly array $podsLibrary,
        private readonly array $containersLibrary,
    ) {
    }

    /**
     * @param array<string, mixed> $vDefinition
     */
    private function buildPersistentVolume(
        string $volumeName,
        string $mountPath,
        array &$vDefinition,
        DefaultsBag $defaultsBag,
        bool $resetOnDeployment,
    ): PersistentVolume {
        $identifier = $vDefinition[self::KEY_STORAGE_IDENTIFIER] ?? $defaultsBag->getReference(
            self::KEY_STORAGE_IDENTIFIER,
        );

        $storageSize = $vDefinition[self::KEY_STORAGE_SIZE] ?? $defaultsBag->getReference(
            self::KEY_STORAGE_SIZE,
        );

        return new PersistentVolume(
            $volumeName,
            $mountPath,
            $identifier,
            $storageSize,
            $resetOnDeployment,
            !empty($vDefinition[self::KEY_WRITE_MANY]),
        );
    }

    /**
     * @param array<string, mixed> $volumeDefinition
     */
    private function buildSecretVolume(
        string $volumeName,
        string $mountPath,
        array &$volumeDefinition
    ): SecretVolume {
        return new SecretVolume(
            $volumeName,
            $mountPath,
            $volumeDefinition[self::KEY_FROM_SECRET]
        );
    }

    /**
     * @param array<string, mixed> $volumeDefinition
     */
    private function buildMapVolume(
        string $volumeName,
        string $mountPath,
        array &$volumeDefinition
    ): MapVolume {
        return new MapVolume(
            $volumeName,
            $mountPath,
            $volumeDefinition[self::KEY_FROM_MAP]
        );
    }

    /**
     * @param array<string, mixed> $volumeDefinition
     */
    private function buildVolume(
        string $volumeName,
        string $mountPath,
        array &$volumeDefinition
    ): Volume {
        return new Volume(
            name: $volumeName,
            paths: $volumeDefinition[self::KEY_ADD],
            localPath: self::VALUE_DEFAULT_LOCAL_PATH_IN_VOLUME,
            mountPath: $mountPath,
            writables: $volumeDefinition[self::KEY_WRITABLES] ?? [],
            isEmbedded: true
        );
    }

    /**
     * @param array<string, mixed> $volumes
     * @param array<string, EmbeddedVolumeImage> $embeddedVolumes
     * @param array<string, VolumeInterface> $containerVolumes
     */
    private function processVolumes(
        array $volumes,
        array &$embeddedVolumes,
        array &$containerVolumes,
        CompiledDeploymentInterface $compiledDeployment,
        DefaultsBag $defaultsBag,
        bool &$isStateless,
        string $hashName,
    ): void {
        foreach ($volumes as $volumeName => &$volumeDefinition) {
            if (empty($volumeDefinition[self::KEY_MOUNT_PATH])) {
                throw new MissingAttributeException("Missing 'mount-path' in $volumeName pod volume definition");
            }

            $mountPath = $volumeDefinition[self::KEY_MOUNT_PATH];

            if (isset($volumeDefinition[self::KEY_PERSISTENT])) {
                $isStateless = false;
                $containerVolumes[(string) $volumeName] = $this->buildPersistentVolume(
                    $volumeDefinition[self::KEY_VOLUME_NAME] ?? $volumeName . '-' . $hashName,
                    $mountPath,
                    $volumeDefinition,
                    $defaultsBag,
                    !empty($volumeDefinition[self::KEY_RESET_ON_DEPLOYMENT] ?? false),
                );

                continue;
            }

            if (isset($volumeDefinition[self::KEY_FROM_SECRET])) {
                $containerVolumes[(string) $volumeName] = $this->buildSecretVolume(
                    $volumeName,
                    $mountPath,
                    $volumeDefinition
                );

                continue;
            }

            if (isset($volumeDefinition[self::KEY_FROM_MAP])) {
                $containerVolumes[(string) $volumeName] = $this->buildMapVolume(
                    $volumeName,
                    $mountPath,
                    $volumeDefinition
                );

                continue;
            }

            if (!isset($volumeDefinition[self::KEY_FROM])) {
                $embeddedVolumes[(string) $volumeName] = $this->buildVolume(
                    $volumeName . '-' . $hashName,
                    $mountPath,
                    $volumeDefinition
                );

                continue;
            }

            $volumeFrom = $volumeDefinition[self::KEY_FROM];
            $compiledDeployment->importVolume(
                $volumeFrom,
                $mountPath,
                $promise = new Promise(
                    static fn (VolumeInterface $volume) => $volume,
                    static fn (#[SensitiveParameter] Throwable $error): never => throw $error,
                )
            );
            $containerVolumes[(string) $volumeName] = $promise->fetchResultIfCalled();
        }
    }

    /**
     * @param array<string, mixed> $embeddedVolumes
     */
    private function processEmbeddedVolumes(
        array &$embeddedVolumes,
        string $image,
        string $version,
        string $originalVersion,
        CompiledDeploymentInterface $compiledDeployment,
    ): string {
        $originalImage = $image;

        $image = $originalImage;
        $parts = explode('/', $image);
        $image = array_pop($parts);

        $embeddedImage = new EmbeddedVolumeImage(
            name: $image,
            tag: $version,
            originalName: $originalImage,
            originalTag: $originalVersion,
            volumes: $embeddedVolumes
        );

        $compiledDeployment->addBuildable($embeddedImage);

        return $image;
    }

    /**
     * @param array<string, mixed> $variables
     * @return array<string, mixed>
     */
    private function processVariables(array $variables): array
    {
        if (isset($variables[self::KEY_IMPORT_SECRETS])) {
            $index = 0;
            foreach ($variables[self::KEY_IMPORT_SECRETS] as $name) {
                $variables[self::KEY_IMPORT_SECRETS . '-' . $index++] = new SecretReference($name, null, true);
            }

            unset($variables[self::KEY_IMPORT_SECRETS]);
        }

        if (isset($variables[self::KEY_FROM_SECRETS])) {
            foreach ($variables[self::KEY_FROM_SECRETS] as $varName => $key) {
                $variables[(string) $varName] = new SecretReference(...explode('.', (string) $key));
            }

            unset($variables[self::KEY_FROM_SECRETS]);
        }

        if (isset($variables[self::KEY_IMPORT_MAPS])) {
            $index = 0;
            foreach ($variables[self::KEY_IMPORT_MAPS] as $name) {
                $variables[self::KEY_IMPORT_MAPS . '-' . $index++] = new MapReference($name, null, true);
            }

            unset($variables[self::KEY_IMPORT_MAPS]);
        }

        if (isset($variables[self::KEY_FROM_MAPS])) {
            foreach ($variables[self::KEY_FROM_MAPS] as $varName => $key) {
                $variables[(string) $varName] = new MapReference(...explode('.', (string) $key));
            }

            unset($variables[self::KEY_FROM_MAPS]);
        }

        return $variables;
    }

    /**
     * @param array<string, mixed> $definitions
     * @param PromiseInterface<Pod, Pod> $promise
     */
    public function processSetOfPods(
        #[SensitiveParameter] array &$definitions,
        CompiledDeploymentInterface $compiledDeployment,
        #[SensitiveParameter] JobUnitInterface $job,
        ResourceManager $resourceManager,
        DefaultsBag $defaultsBag,
        PromiseInterface $promise,
        string $parentHashName = '',
    ): void {
        try {
            $ociKey = self::KEY_OCI_REGISTRY_CONFIG_NAME;

            foreach ($definitions as $nameSet => &$podsList) {
                $containers = [];
                $isStateless = true;
                $numberOfReplicas = (int)($podsList[self::KEY_REPLICAS] ?? 1);

                $podHashName = substr(hash('sha256', $nameSet), 0, 5);

                foreach ($podsList[self::KEY_CONTAINERS] as $name => &$config) {
                    $containerVolumes = [];
                    $embeddedVolumes = [];

                    $containerHashName = substr(hash('sha256', $name), 0, 5);

                    $hashName = trim(implode('-', [$parentHashName, $podHashName, $containerHashName]), '-');

                    $this->processVolumes(
                        $config[self::KEY_VOLUMES] ?? [],
                        $embeddedVolumes,
                        $containerVolumes,
                        $compiledDeployment,
                        $defaultsBag,
                        $isStateless,
                        $hashName,
                    );

                    $image = $config[self::KEY_IMAGE];
                    $version = (string)($config[self::KEY_VERSION] ?? '');
                    $buildedVersion = trim(
                        implode(
                            '-',
                            [
                                str_replace(self::VALUE_LATEST, '', $version),
                                $hashName,
                                $job->getEnvironmentTag(),
                            ],
                        ),
                        '-',
                    );

                    if (!empty($embeddedVolumes)) {
                        $image = $this->processEmbeddedVolumes(
                            embeddedVolumes: $embeddedVolumes,
                            image: $image,
                            version: $buildedVersion,
                            originalVersion: $version,
                            compiledDeployment: $compiledDeployment,
                        );

                        $version = $buildedVersion;
                    }

                    $variables = $this->processVariables($config[self::KEY_VARIABLES] ?? []);

                    $healthCheck = null;
                    if (!empty($config[self::KEY_HEALTHCHECK])) {
                        $hcc = $config[self::KEY_HEALTHCHECK];
                        $probe = $hcc[self::KEY_PROBE];
                        if (!empty($probe[self::KEY_COMMAND])) {
                            $hcType = HealthCheckType::Command;
                        } elseif (!empty($probe[self::KEY_TCP])) {
                            $hcType = HealthCheckType::Tcp;
                        } else {
                            $hcType = HealthCheckType::Http;
                        }

                        $port = null;
                        if (!empty($probe[self::KEY_TCP][self::KEY_PORT])) {
                            $port = (int)$probe[self::KEY_TCP][self::KEY_PORT];
                        }
                        if (!empty($probe[self::KEY_HTTP][self::KEY_PORT])) {
                            $port = (int)$probe[self::KEY_HTTP][self::KEY_PORT];
                        }

                        $healthCheck = new HealthCheck(
                            initialDelay: (int)($hcc[self::KEY_INITIAL_DELAY_SCDS]),
                            period: (int)($hcc[self::KEY_PERIOD_SCDS]),
                            type: $hcType,
                            command: $probe[self::KEY_COMMAND] ?? null,
                            port: $port,
                            path: $probe[self::KEY_HTTP][self::KEY_PATH] ?? null,
                            isSecure: !empty($probe[self::KEY_HTTP][self::KEY_IS_SECURE]),
                            successThreshold: (int)($hcc[self::KEY_THRESHOLD][self::KEY_SUCCESS] ?? 1),
                            failureThreshold: (int)($hcc[self::KEY_THRESHOLD][self::KEY_FAILURE] ?? 1),
                        );
                    }

                    $resourceSet = new ResourceSet();
                    $resourcesRequired = [];
                    if (!empty($config[self::KEY_RESOURCES])) {
                        foreach ($config[self::KEY_RESOURCES] as $resource) {
                            $resourcesRequired[(string)$resource[self::KEY_TYPE]] = true;
                            $resourceManager->reserve(
                                resourceType: $resource[self::KEY_TYPE],
                                require: $resource[self::KEY_REQUIRE],
                                limit: $resource[self::KEY_LIMIT] ?? $resource[self::KEY_REQUIRE],
                                numberOfReplicas: $numberOfReplicas,
                                resourceSet: $resourceSet,
                            );
                        }
                    }

                    $resourceManager->prepareAutomaticsReservations(
                        resourceSet: $resourceSet,
                        numberOfReplicas: $numberOfReplicas,
                        resourceTypeToExclude: array_keys($resourcesRequired),
                    );

                    $containers[] = new Container(
                        name: $name,
                        image: $image,
                        version: $version,
                        listen: (array)array_map(intval(...), (array)($config[self::KEY_LISTEN] ?? [])),
                        volumes: $containerVolumes,
                        variables: $variables,
                        healthCheck: $healthCheck,
                        resources: $resourceSet,
                    );
                }
                unset($config);

                $fsGroup = null;
                if (isset($podsList[self::KEY_SECURITY][self::KEY_FS_GROUP])) {
                    $fsGroup = (int)$podsList[self::KEY_SECURITY][self::KEY_FS_GROUP];
                }

                $upgradeStrategy = UpgradeStrategy::RollingUpgrade;
                if (isset($podsList[self::KEY_UPGRADE][self::KEY_STRATEGY])) {
                    $upgradeStrategy = UpgradeStrategy::from($podsList[self::KEY_UPGRADE][self::KEY_STRATEGY]);
                }

                $restartPolicy = null;
                if (!empty($podsList[self::KEY_RESTART_POLICY])) {
                    $restartPolicy = RestartPolicy::from($podsList[self::KEY_RESTART_POLICY]);
                }

                $ociRegistryConfig = $podsList[$ociKey] ?? $defaultsBag->getReference($ociKey);
                $promise->success(
                    new Pod(
                        name: $nameSet,
                        replicas: $numberOfReplicas,
                        containers: $containers,
                        ociRegistryConfigName: $ociRegistryConfig,
                        maxUpgradingPods: (int)($podsList[self::KEY_UPGRADE][self::KEY_MAX_UPGRADING_PODS] ?? 1),
                        maxUnavailablePods: (int)($podsList[self::KEY_UPGRADE][self::KEY_MAX_UNAVAILABLE_PODS] ?? 0),
                        upgradeStrategy: $upgradeStrategy,
                        fsGroup: $fsGroup,
                        requires: (array)($podsList[self::KEY_REQUIRES] ?? []),
                        isStateless: $isStateless,
                        restartPolicy: $restartPolicy,
                    )
                );
            }

            unset($podsList);
        } catch (Throwable $e) {
            $promise->fail($e);
        }
    }

    public function compile(
        #[SensitiveParameter] array &$definitions,
        CompiledDeploymentInterface $compiledDeployment,
        #[SensitiveParameter] JobWorkspaceInterface $workspace,
        #[SensitiveParameter] JobUnitInterface $job,
        ResourceManager $resourceManager,
        DefaultsBag $defaultsBag,
    ): CompilerInterface {
        $this->processSetOfPods(
            definitions: $definitions,
            compiledDeployment: $compiledDeployment,
            job: $job,
            resourceManager: $resourceManager,
            defaultsBag: $defaultsBag,
            promise: new Promise(
                fn (Pod $pod) => $compiledDeployment->addPod($pod->getName(), $pod),
                fn (#[SensitiveParameter] Throwable $error): never => throw $error,
            ),
        );

        return $this;
    }

    public function extends(
        array &$definitions,
    ): ExtenderInterface {
        foreach ($definitions as &$config) {
            if (isset($config[self::KEY_EXTENDS])) {
                $libName = $config[self::KEY_EXTENDS];
                if (!is_string($libName)) {
                    throw new InvalidArgumentException("teknoo.east.paas.error.recipe.job.extends-need-string", 400);
                }

                if (!isset($this->podsLibrary[$libName])) {
                    throw new DomainException(
                        "teknoo.east.paas.error.recipe.job.extends-not-available:pods:$libName",
                        400
                    );
                }

                $config = self::arrayMergeRecursiveDistinct($this->podsLibrary[$libName], $config);
            }

            foreach ($config[self::KEY_CONTAINERS] as &$subCconfig) {
                if (!isset($subCconfig[self::KEY_EXTENDS])) {
                    continue;
                }

                $libName = $subCconfig[self::KEY_EXTENDS];
                if (!is_string($libName)) {
                    throw new InvalidArgumentException("teknoo.east.paas.error.recipe.job.extends-need-string", 400);
                }

                if (!isset($this->containersLibrary[$libName])) {
                    throw new DomainException(
                        "teknoo.east.paas.error.recipe.job.extends-not-available:containers:$libName",
                        400
                    );
                }

                $subCconfig = self::arrayMergeRecursiveDistinct($this->containersLibrary[$libName], $subCconfig);
            }
        }

        return $this;
    }
}
