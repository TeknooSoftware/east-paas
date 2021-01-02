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
use Teknoo\East\Paas\Container\Expose\Service;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
trait ServiceTrait
{
    private static string $keyServicePorts = 'ports';
    private static string $keyServiceListen = 'listen';
    private static string $keyServiceTarget = 'target';
    private static string $keyServicePodName = 'pod';
    private static string $keyServiceProtocol = 'protocol';
    private static string $keyServiceInternal = 'internal';

    private function compileServices(
        CompiledDeployment $compiledDeployment
    ): callable {
        return static function ($servicesConfigs) use ($compiledDeployment): void {
            foreach ($servicesConfigs as $name => &$config) {
                $ports = [];
                foreach ($config[static::$keyServicePorts] as $row) {
                    $ports[(int) $row[static::$keyServiceListen]] = (int) $row[static::$keyServiceTarget];
                }

                $compiledDeployment->addService(
                    $name,
                    new Service(
                        $name,
                        $config[static::$keyServicePodName] ?? $name,
                        $ports,
                        $config[static::$keyServiceProtocol] ?? Service::TCP,
                        !empty($config[static::$keyServiceInternal])
                    )
                );
            }
        };
    }
}
