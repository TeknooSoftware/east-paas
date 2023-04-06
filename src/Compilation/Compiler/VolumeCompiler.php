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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Compilation\Compiler;

use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Contracts\Compilation\CompilerInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * Compilation module able to convert `volume` sections in paas.yaml file as Volume instance.
 * The Volume instance will be pushed into the CompiledDeploymentInterface instance.
 * The Volume is a basic volume, embedding files and data from the source repository, to share between pods
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class VolumeCompiler implements CompilerInterface
{
    private const KEY_ADD = 'add';
    private const KEY_LOCAL_PATH = 'local-path';
    private const KEY_MOUNT_PATH = 'mount-path';
    private const DEFAULT_LOCAL_PATH_IN_VOLUME = '/volume';
    private const DEFAULT_MOUNT_PATH_IN_VOLUME = '/mnt';

    public function compile(
        array &$definitions,
        CompiledDeploymentInterface $compiledDeployment,
        JobWorkspaceInterface $workspace,
        JobUnitInterface $job,
        ?string $storageIdentifier = null,
        ?string $defaultStorageSize = null,
        ?string $ociRegistryConfig = null,
    ): CompilerInterface {
        foreach ($definitions as $name => &$config) {
            $volumeName = ($config['name'] ?? $name);

            $compiledDeployment->addVolume(
                $volumeName,
                $volume = new Volume(
                    $volumeName . '-' . $job->getShortId(),
                    $config[self::KEY_ADD],
                    $config[self::KEY_LOCAL_PATH] ?? self::DEFAULT_LOCAL_PATH_IN_VOLUME,
                    $config[self::KEY_MOUNT_PATH] ?? self::DEFAULT_MOUNT_PATH_IN_VOLUME
                )
            );

            $volumes[$volumeName] = $volume;
        }

        return $this;
    }
}
