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
use Teknoo\East\Paas\Container\Volume\Volume;
use Teknoo\East\Paas\Contracts\Container\PopulatedVolumeInterface;
use Teknoo\East\Paas\Contracts\Container\VolumeInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
trait VolumeTrait
{
    private static string $keyVolumeAdd = 'add';
    private static string $keyVolumeLocalPath = 'local-path';
    private static string $keyVolumeMountPath = 'mount-path';

    /**
     * @param array<string, PopulatedVolumeInterface> $volumes
     */
    private function compileVolumes(
        CompiledDeployment $compiledDeployment,
        string $jobId,
        array &$volumes
    ): callable {
        return static function ($volumesConfigs) use ($compiledDeployment, $jobId, &$volumes): void {
            if (empty($volumesConfigs)) {
                return;
            }

            foreach ($volumesConfigs as $name => &$config) {
                $volumeName = ($config['name'] ?? $name);

                $compiledDeployment->defineVolume(
                    $volumeName,
                    $volume = new Volume(
                        $volumeName . $jobId,
                        $config[static::$keyVolumeAdd],
                        $config[static::$keyVolumeLocalPath] ?? static::DEFAULT_LOCAL_PATH_IN_VOLUME,
                        $config[static::$keyVolumeMountPath] ?? static::DEFAULT_MOUNT_PATH_IN_VOLUME
                    )
                );

                $volumes[$volumeName] = $volume;
            }
        };
    }
}
