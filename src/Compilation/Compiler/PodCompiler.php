<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
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

namespace Teknoo\East\Paas\Compilation\Compiler;

use RuntimeException;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheck;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheckType;
use Teknoo\East\Paas\Compilation\CompiledDeployment\MapReference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\UpgradeStrategy;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\MapVolume;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Container;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\EmbeddedVolumeImage;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\SecretReference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\PersistentVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\SecretVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Contracts\Compilation\CompilerInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\VolumeInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Throwable;

use function array_map;
use function array_pop;
use function explode;

/**
 * Compilation module able to convert `pods` sections in paas.yaml file as Pod instance.
 * The Pod instance will be pushed into the CompiledDeploymentInterface instance.
 * If the pod define also some secrets, embedded volumes or persistent volumes, SecretVolume, EmbeddedVolume and
 * PersistentVolume will be also created and added to the CompiledDeploymentInterface instance and referenced
 * with the Pod instance.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class PodCompiler implements CompilerInterface
{
    private const KEY_CONTAINERS = 'containers';
    private const KEY_OCI_REGISTRY_CONFIG_NAME = 'oci-registry-config-name';
    private const KEY_UPGRADE = 'upgrade';
    private const KEY_SECURITY = 'security';
    private const KEY_MAX_UPGRADING_PODS = 'max-upgrading-pods';
    private const KEY_FS_GROUP = 'fs-group';
    private const KEY_MAX_UNAVAILABLE_PODS = 'max-unavailable-pods';
    private const KEY_STRATEGY = 'strategy';
    private const KEY_VOLUMES = 'volumes';
    private const KEY_MOUNT_PATH = 'mount-path';
    private const KEY_FROM = 'from';
    private const KEY_PERSISTENT = 'persistent';
    private const KEY_RESET_ON_DEPLOYMENT = 'reset-on-deployment';
    private const KEY_FROM_SECRET = 'from-secret';
    private const KEY_IMPORT_SECRETS = 'import-secrets';
    private const KEY_FROM_SECRETS = 'from-secrets';
    private const KEY_FROM_MAP = 'from-map';
    private const KEY_IMPORT_MAPS = 'import-maps';
    private const KEY_FROM_MAPS = 'from-maps';
    private const KEY_STORAGE_IDENTIFIER = 'storage-provider';
    private const KEY_STORAGE_SIZE = 'storage-size';
    private const KEY_ADD = 'add';
    private const KEY_WRITABLES = 'writables';
    private const KEY_IMAGE = 'image';
    private const KEY_VERSION = 'version';
    private const KEY_LISTEN = 'listen';
    private const KEY_VARIABLES = 'variables';
    private const KEY_REPLICAS = 'replicas';
    private const KEY_HEALTHCHECK = 'healthcheck';
    private const KEY_INITIAL_DELAY_SCDS = 'initial-delay-seconds';
    private const KEY_PERIOD_SCDS = 'period-seconds';
    private const KEY_PROBE = 'probe';
    private const KEY_COMMAND = 'command';
    private const KEY_TCP = 'tcp';
    private const KEY_HTTP = 'http';
    private const KEY_PORT = 'port';
    private const KEY_PATH = 'path';
    private const KEY_IS_SECURE = 'is-secure';
    private const KEY_THRESHOLD = 'threshold';
    private const KEY_FAILURE = 'failure';
    private const KEY_SUCCESS = 'success';
    private const VALUE_LATEST = 'latest';
    private const VALUE_DEFAULT_LOCAL_PATH_IN_VOLUME = '/volume';

    /**
     * @param array<string, mixed> $volumeDefinition
     */
    private function buildPersistentVolume(
        string $volumeName,
        string $mountPath,
        array &$volumeDefinition,
        ?string $storageIdentifier,
        ?string $defaultStorageSize,
        bool $resetOnDeployment,
    ): PersistentVolume {
        $identifier = $volumeDefinition[self::KEY_STORAGE_IDENTIFIER] ?? $storageIdentifier;

        if (empty($identifier)) {
            throw new RuntimeException("Missing 'storage-provider' in $volumeName pod volume definition");
        }

        $storageSize = $volumeDefinition[self::KEY_STORAGE_SIZE] ?? $defaultStorageSize;

        if (empty($storageSize)) {
            throw new RuntimeException("Missing 'storage-size' in $volumeName pod volume definition");
        }

        return new PersistentVolume(
            $volumeName,
            $mountPath,
            $identifier,
            $storageSize,
            $resetOnDeployment
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
        ?string $storageIdentifier,
        ?string $defaultStorageSize,
    ): void {
        foreach ($volumes as $volumeName => &$volumeDefinition) {
            if (empty($volumeDefinition[self::KEY_MOUNT_PATH])) {
                throw new RuntimeException("Missing 'mount-path' in $volumeName pod volume definition");
            }

            $mountPath = $volumeDefinition[self::KEY_MOUNT_PATH];

            if (isset($volumeDefinition[self::KEY_PERSISTENT])) {
                $containerVolumes[(string) $volumeName] = $this->buildPersistentVolume(
                    $volumeName,
                    $mountPath,
                    $volumeDefinition,
                    $storageIdentifier,
                    $defaultStorageSize,
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
                    $volumeName,
                    $mountPath,
                    $volumeDefinition
                );

                continue;
            }

            $volumeFrom = $volumeDefinition[self::KEY_FROM];
            $compiledDeployment->importVolume(
                $volumeFrom,
                $mountPath,
                new Promise(
                    static function (VolumeInterface $volume) use (&$containerVolumes, $volumeName): void {
                        $containerVolumes[(string) $volumeName] = $volume;
                    },
                    static function (Throwable $error): never {
                        throw $error;
                    }
                )
            );
        }
    }

    /**
     * @param array<string, mixed> $embeddedVolumes
     */
    private function processEmbeddedVolumes(
        array &$embeddedVolumes,
        string $image,
        string $version,
        CompiledDeploymentInterface $compiledDeployment,
        JobUnitInterface $job
    ): string {
        $originalImage = $image;

        $image = $originalImage . '-' . $job->getShortId();
        $parts = explode('/', $image);
        $image = array_pop($parts);

        $embeddedImage = new EmbeddedVolumeImage(
            $image,
            $version,
            $originalImage,
            $embeddedVolumes
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

    public function compile(
        array &$definitions,
        CompiledDeploymentInterface $compiledDeployment,
        JobWorkspaceInterface $workspace,
        JobUnitInterface $job,
        ?string $storageIdentifier = null,
        ?string $defaultStorageSize = null,
        ?string $ociRegistryConfig = null,
    ): CompilerInterface {
        foreach ($definitions as $nameSet => &$podsList) {
            $containers = [];
            foreach ($podsList[self::KEY_CONTAINERS] as $name => &$config) {
                $containerVolumes = [];
                $embeddedVolumes = [];

                $this->processVolumes(
                    $config[self::KEY_VOLUMES] ?? [],
                    $embeddedVolumes,
                    $containerVolumes,
                    $compiledDeployment,
                    $storageIdentifier,
                    $defaultStorageSize,
                );

                $image = $config[self::KEY_IMAGE];
                $version = (string)($config[self::KEY_VERSION] ?? self::VALUE_LATEST);

                if (!empty($embeddedVolumes)) {
                    $image = $this->processEmbeddedVolumes(
                        $embeddedVolumes,
                        $image,
                        $version,
                        $compiledDeployment,
                        $job
                    );
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
                        $port = (int) $probe[self::KEY_TCP][self::KEY_PORT];
                    }
                    if (!empty($probe[self::KEY_HTTP][self::KEY_PORT])) {
                        $port = (int) $probe[self::KEY_HTTP][self::KEY_PORT];
                    }

                    $healthCheck = new HealthCheck(
                        initialDelay: (int) ($hcc[self::KEY_INITIAL_DELAY_SCDS]),
                        period: (int) ($hcc[self::KEY_PERIOD_SCDS]),
                        type: $hcType,
                        command: $probe[self::KEY_COMMAND] ?? null,
                        port: $port,
                        path: $probe[self::KEY_HTTP][self::KEY_PATH] ?? null,
                        isSecure: !empty($probe[self::KEY_HTTP][self::KEY_IS_SECURE]),
                        successThreshold: (int) ($hcc[self::KEY_THRESHOLD][self::KEY_SUCCESS] ?? 1),
                        failureThreshold: (int) ($hcc[self::KEY_THRESHOLD][self::KEY_FAILURE] ?? 1),
                    );
                }

                $containers[] = new Container(
                    $name,
                    $image,
                    $version,
                    (array) array_map('intval', (array) ($config[self::KEY_LISTEN] ?? [])),
                    $containerVolumes,
                    $variables,
                    $healthCheck,
                );
            }

            $fsGroup = null;
            if (isset($podsList[self::KEY_SECURITY][self::KEY_FS_GROUP])) {
                $fsGroup = (int) $podsList[self::KEY_SECURITY][self::KEY_FS_GROUP];
            }

            $upgradeStrategy = UpgradeStrategy::RollingUpgrade;
            if (isset($podsList[self::KEY_UPGRADE][self::KEY_STRATEGY])) {
                $upgradeStrategy = UpgradeStrategy::from($podsList[self::KEY_UPGRADE][self::KEY_STRATEGY]);
            }

            $compiledDeployment->addPod(
                $nameSet,
                new Pod(
                    name: $nameSet,
                    replicas: (int) ($podsList[self::KEY_REPLICAS] ?? 1),
                    containers: $containers,
                    ociRegistryConfigName: $podsList[self::KEY_OCI_REGISTRY_CONFIG_NAME] ?? $ociRegistryConfig,
                    maxUpgradingPods: (int) ($podsList[self::KEY_UPGRADE][self::KEY_MAX_UPGRADING_PODS] ?? 1),
                    maxUnavailablePods: (int) ($podsList[self::KEY_UPGRADE][self::KEY_MAX_UNAVAILABLE_PODS] ?? 0),
                    upgradeStrategy: $upgradeStrategy,
                    fsGroup: $fsGroup,
                )
            );
        }

        return $this;
    }
}
