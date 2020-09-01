<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Conductor\Compilation;

use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Container\Service;

trait ServiceTrait
{
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
                    $ports[(int) $row['listen']] = (int) $row['target'];
                }

                $compiledDeployment->addService(
                    $name,
                    new Service(
                        $name,
                        $ports,
                        $config['protocol'] ?? Service::TCP
                    )
                );
            }
        };
    }
}
