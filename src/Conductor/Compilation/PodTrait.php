<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Conductor\Compilation;

use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Container\Container;
use Teknoo\East\Paas\Container\Pod;

trait PodTrait
{
    private function compilePods(CompiledDeployment $compiledDeployment): callable
    {
        return static function (array $podsConfiguration) use ($compiledDeployment): void {
            if (empty($podsConfiguration)) {
                throw new \UnexpectedValueException('Pods are not defined in the configuration');
            }

            foreach ($podsConfiguration as $nameSet => &$podsList) {
                $containers = [];
                foreach ($podsList['containers'] as $name => &$config) {
                    $version = (string)($config['version'] ?? 'lastest');
                    $containers[] = new Container(
                        $name,
                        $config['image'],
                        $version,
                        (array) \array_map('intval', (array) $config['listen']),
                        $config['volumes'] ?? [],
                        $config['variables'] ?? []
                    );
                }

                $compiledDeployment->addPod(
                    $nameSet,
                    new Pod($nameSet, (int)($podsList['replicas'] ?? 1), $containers)
                );
            }
        };
    }
}
