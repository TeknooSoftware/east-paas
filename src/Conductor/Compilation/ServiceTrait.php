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
use Teknoo\East\Paas\Container\Service;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
trait ServiceTrait
{
    private static string $keyServiceListen = 'listen';
    private static string $keyServiceTarget = 'target';
    private static string $keyServiceProtocol = 'protocol';

    private function compileServices(
        CompiledDeployment $compiledDeployment
    ): callable {
        return static function ($servicesConfigs) use ($compiledDeployment): void {
            if (empty($servicesConfigs)) {
                throw new \UnexpectedValueException('Services are not defined in the configuration');
            }

            foreach ($servicesConfigs as $name => &$config) {
                $ports = [];
                foreach ($config as $row) {
                    $ports[(int) $row[self::$keyServiceListen]] = (int) $row[self::$keyServiceTarget];
                }

                $compiledDeployment->addService(
                    $name,
                    new Service(
                        $name,
                        $ports,
                        $config[self::$keyServiceProtocol] ?? Service::TCP
                    )
                );
            }
        };
    }
}
