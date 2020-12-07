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

namespace Teknoo\East\Paas\Conductor\Compilation;

use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Container\Container;
use Teknoo\East\Paas\Container\EmbeddedVolumeImage;
use Teknoo\East\Paas\Container\Image;
use Teknoo\East\Paas\Container\PersistentVolume;
use Teknoo\East\Paas\Container\Pod;
use Teknoo\East\Paas\Container\Volume;
use Teknoo\East\Paas\Contracts\Container\PopulatedVolumeInterface;
use Teknoo\East\Paas\Contracts\Container\VolumeInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
trait PodTrait
{
    private static string $keyPodContainers = 'containers';
    private static string $keyPodVolumes = 'volumes';
    private static string $keyPodMountPath = 'mount-path';
    private static string $keyPodLocalPath = 'local-path';
    private static string $keyPodFrom = 'from';
    private static string $keyPodPersistent = 'persistent';
    private static string $keyPodStorageIdentifier = 'storage-provider';
    private static string $keyPodAdd = 'add';
    private static string $keyPodImage = 'image';
    private static string $keyPodVersion = 'version';
    private static string $keyPodLatest = 'latest';
    private static string $keyPodListen = 'listen';
    private static string $keyPodVariables = 'variables';
    private static string $keyPodReplicas = 'replicas';

    private string $defaultStorageIdentifier;

    /**
     * @param array<string, PopulatedVolumeInterface> $volumes
     */
    private function compilePods(CompiledDeployment $compiledDeployment, array &$volumes): callable
    {
        return function (array $podsConfiguration) use ($compiledDeployment, &$volumes): void {
            if (empty($podsConfiguration)) {
                throw new \UnexpectedValueException('Pods are not defined in the configuration');
            }

            foreach ($podsConfiguration as $nameSet => &$podsList) {
                $containers = [];
                foreach ($podsList[self::$keyPodContainers] as $name => &$config) {
                    $containerVolumes = [];

                    $embeddedVolumes = [];
                    foreach ($config[self::$keyPodVolumes] ?? [] as $volumeName => $volumeDefinition) {
                        if (!isset($volumeDefinition[self::$keyPodMountPath])) {
                            throw new \DomainException(
                                "Missing attribute mount_path in $volumeName"
                            );
                        }

                        $mountPath = $volumeDefinition[self::$keyPodMountPath];

                        if (isset($volumeDefinition[self::$keyPodPersistent])) {
                            if (
                                !empty($volumeDefinition[self::$keyPodFrom])
                                || !empty($volumeDefinition[self::$keyPodAdd])
                            ) {
                                throw new \DomainException(
                                    "Volume $volumeName can not be persistent and populated"
                                );
                            }

                            $containerVolumes[(string) $volumeName] = new PersistentVolume(
                                $volumeName,
                                $mountPath,
                                $volumeDefinition[self::$keyPodStorageIdentifier] ?? $this->defaultStorageIdentifier
                            );

                            continue;
                        }

                        if (!isset($volumeDefinition[self::$keyPodFrom])) {
                            $embeddedVolumes[(string) $volumeName] = new Volume(
                                $volumeName,
                                $volumeDefinition[self::$keyPodAdd],
                                $volumeDefinition[self::$keyPodLocalPath] ?? self::DEFAULT_LOCAL_PATH_IN_VOLUME,
                                $mountPath,
                                true
                            );

                            continue;
                        }

                        $volumeFrom = $volumeDefinition[self::$keyPodFrom];
                        if (!isset($volumes[$volumeFrom])) {
                            throw new \DomainException(
                                "Volume called $volumeFrom was not found volumes definition"
                            );
                        }

                        $containerVolumes[(string) $volumeName] = $volumes[$volumeFrom]->import($mountPath);
                    }

                    $image = $config[self::$keyPodImage];
                    $version = (string)($config[self::$keyPodVersion] ?? self::$keyPodLatest);

                    if (!empty($embeddedVolumes)) {
                        $originalImage = $image;

                        $image = $originalImage . '_' . $this->job->getId();
                        $parts = \explode('/', $image);
                        $imageName = \array_pop($parts);

                        $embeddedImage = new EmbeddedVolumeImage(
                            $imageName,
                            $version,
                            $originalImage,
                            $embeddedVolumes
                        );

                        if (!empty($parts)) {
                            $embeddedImage = $embeddedImage->withRegistry(\array_pop($parts));
                        }

                        $compiledDeployment->addImage($embeddedImage);
                    }

                    $containers[] = new Container(
                        $name,
                        $image,
                        $version,
                        (array) \array_map('intval', (array) ($config[self::$keyPodListen] ?? [])),
                        $containerVolumes,
                        $config[self::$keyPodVariables] ?? []
                    );
                }

                $compiledDeployment->addPod(
                    $nameSet,
                    new Pod($nameSet, (int)($podsList[self::$keyPodReplicas] ?? 1), $containers)
                );
            }
        };
    }
}
