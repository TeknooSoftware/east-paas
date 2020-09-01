<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Conductor\Compilation;

use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Container\Volume;

trait VolumeTrait
{
    private function compileVolumes(
        CompiledDeployment $compiledDeployment,
        string $jobId
    ): callable {
        return static function ($volumesConfigs) use ($compiledDeployment, $jobId): void {
            if (empty($volumesConfigs)) {
                return;
            }

            foreach ($volumesConfigs as $name => &$config) {
                $volumeName = ($config['name'] ?? $name);

                $compiledDeployment->defineVolume(
                    $volumeName,
                    new Volume(
                        $volumeName . $jobId,
                        $config['target'],
                        $config['add']
                    )
                );
            }
        };
    }
}
