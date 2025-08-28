<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Compilation\Compiler;

use SensitiveParameter;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompilerInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * Compilation module able to convert `volume` sections in paas.yaml file as Volume instance.
 * The Volume instance will be pushed into the CompiledDeploymentInterface instance.
 * The Volume is a basic volume, embedding files and data from the source repository, to share between pods
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class VolumeCompiler implements CompilerInterface
{
    private const string KEY_ADD = 'add';

    private const string KEY_LOCAL_PATH = 'local-path';

    private const string KEY_MOUNT_PATH = 'mount-path';

    private const string DEFAULT_LOCAL_PATH_IN_VOLUME = '/volume';

    private const string DEFAULT_MOUNT_PATH_IN_VOLUME = '/mnt';

    public function compile(
        #[SensitiveParameter] array &$definitions,
        CompiledDeploymentInterface $compiledDeployment,
        #[SensitiveParameter] JobWorkspaceInterface $workspace,
        #[SensitiveParameter] JobUnitInterface $job,
        ResourceManager $resourceManager,
        DefaultsBag $defaultsBag,
    ): CompilerInterface {
        foreach ($definitions as $name => &$config) {
            $volumeName = ($config['name'] ?? $name);

            $compiledDeployment->addVolume(
                $volumeName,
                $volume = new Volume(
                    $volumeName . '-' . $job->getProjectNormalizedName(),
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
