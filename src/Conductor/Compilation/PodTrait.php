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
use Teknoo\East\Paas\Container\Container;
use Teknoo\East\Paas\Container\Pod;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
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
                    $version = (string)($config['version'] ?? 'latest');
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
