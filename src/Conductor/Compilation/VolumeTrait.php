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
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Conductor\Compilation;

use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Container\Volume;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
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
