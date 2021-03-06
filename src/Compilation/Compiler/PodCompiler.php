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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Compilation\Compiler;

use RuntimeException;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Container;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\EmbeddedVolumeImage;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\SecretReference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\PersistentVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\SecretVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Contracts\Conductor\CompilerInterface;
use Teknoo\East\Paas\Contracts\Container\VolumeInterface;
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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class PodCompiler implements CompilerInterface
{
    private const KEY_CONTAINERS = 'containers';
    private const KEY_VOLUMES = 'volumes';
    private const KEY_MOUNT_PATH = 'mount-path';
    private const KEY_FROM = 'from';
    private const KEY_PERSISTENT = 'persistent';
    private const KEY_FROM_SECRET = 'from-secret';
    private const KEY_FROM_SECRETS = 'from-secrets';
    private const KEY_STORAGE_IDENTIFIER = 'storage-provider';
    private const KEY_ADD = 'add';
    private const KEY_IMAGE = 'image';
    private const KEY_VERSION = 'version';
    private const KEY_LISTEN = 'listen';
    private const KEY_VARIABLES = 'variables';
    private const KEY_REPLICAS = 'replicas';
    private const VALUE_LATEST = 'latest';
    private const VALUE_DEFAULT_LOCAL_PATH_IN_VOLUME = '/volume';

    /**
     * @param array<string, mixed> $volumeDefinition
     */
    private function buildPersistentVolume(
        string $volumeName,
        string $mountPath,
        array &$volumeDefinition,
        ?string $storageIdentifier
    ): PersistentVolume {
        $identifier = $volumeDefinition[static::KEY_STORAGE_IDENTIFIER] ?? $storageIdentifier;

        if (empty($identifier)) {
            throw new RuntimeException("Missing 'storage-provider' in $volumeName pod volume definition");
        }

        return new PersistentVolume(
            $volumeName,
            $mountPath,
            $identifier
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
            $volumeDefinition[static::KEY_FROM_SECRET]
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
            $volumeName,
            $volumeDefinition[static::KEY_ADD],
            static::VALUE_DEFAULT_LOCAL_PATH_IN_VOLUME,
            $mountPath,
            true
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
        ?string $storageIdentifier
    ): void {
        foreach ($volumes as $volumeName => &$volumeDefinition) {
            if (empty($volumeDefinition[static::KEY_MOUNT_PATH])) {
                throw new RuntimeException("Missing 'mount-path' in $volumeName pod volume definition");
            }

            $mountPath = $volumeDefinition[static::KEY_MOUNT_PATH];

            if (isset($volumeDefinition[static::KEY_PERSISTENT])) {
                $containerVolumes[(string) $volumeName] = $this->buildPersistentVolume(
                    $volumeName,
                    $mountPath,
                    $volumeDefinition,
                    $storageIdentifier
                );

                continue;
            }

            if (isset($volumeDefinition[static::KEY_FROM_SECRET])) {
                $containerVolumes[(string) $volumeName] = $this->buildSecretVolume(
                    $volumeName,
                    $mountPath,
                    $volumeDefinition
                );

                continue;
            }

            if (!isset($volumeDefinition[static::KEY_FROM])) {
                $embeddedVolumes[(string) $volumeName] = $this->buildVolume(
                    $volumeName,
                    $mountPath,
                    $volumeDefinition
                );

                continue;
            }

            $volumeFrom = $volumeDefinition[static::KEY_FROM];
            $compiledDeployment->importVolume(
                $volumeFrom,
                $mountPath,
                new Promise(
                    fn (VolumeInterface $volume) => $containerVolumes[(string) $volumeName] = $volume,
                    static function (Throwable $error) {
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

        $image = $originalImage . '_' . $job->getId();
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
        if (isset($variables[static::KEY_FROM_SECRETS])) {
            foreach ($variables[static::KEY_FROM_SECRETS] as $varName => $key) {
                $variables[(string) $varName] = new SecretReference(...explode('.', $key));
            }
            unset($variables[static::KEY_FROM_SECRETS]);
        }

        return $variables;
    }

    public function compile(
        array &$definitions,
        CompiledDeploymentInterface $compiledDeployment,
        JobWorkspaceInterface $workspace,
        JobUnitInterface $job,
        ?string $storageIdentifier = null
    ): CompilerInterface {
        foreach ($definitions as $nameSet => &$podsList) {
            $containers = [];
            foreach ($podsList[static::KEY_CONTAINERS] as $name => &$config) {
                $containerVolumes = [];
                $embeddedVolumes = [];

                $this->processVolumes(
                    $config[static::KEY_VOLUMES] ?? [],
                    $embeddedVolumes,
                    $containerVolumes,
                    $compiledDeployment,
                    $storageIdentifier
                );


                $image = $config[static::KEY_IMAGE];
                $version = (string)($config[static::KEY_VERSION] ?? static::VALUE_LATEST);

                if (!empty($embeddedVolumes)) {
                    $image = $this->processEmbeddedVolumes(
                        $embeddedVolumes,
                        $image,
                        $version,
                        $compiledDeployment,
                        $job
                    );
                }

                $variables = $this->processVariables($config[static::KEY_VARIABLES] ?? []);

                $containers[] = new Container(
                    $name,
                    $image,
                    $version,
                    (array) array_map('intval', (array) ($config[static::KEY_LISTEN] ?? [])),
                    $containerVolumes,
                    $variables
                );
            }

            $compiledDeployment->addPod(
                $nameSet,
                new Pod($nameSet, (int)($podsList[static::KEY_REPLICAS] ?? 1), $containers)
            );
        }

        return $this;
    }
}
