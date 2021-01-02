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
use Teknoo\East\Paas\Container\Expose\Ingress;
use Teknoo\East\Paas\Container\Expose\IngressPath;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
trait IngressTrait
{
    private static string $keyIngressHost = 'host';
    private static string $keyIngressTls = 'tls';
    private static string $keyIngressProvider = 'provider';
    private static string $keyIngressSecret = 'secret';
    private static string $keyIngressService = 'service';
    private static string $keyIngressServiceName = 'name';
    private static string $keyIngressPort = 'port';
    private static string $keyIngressPaths = 'paths';
    private static string $keyIngressPath = 'path';

    private function compileIngresses(
        CompiledDeployment $compiledDeployment
    ): callable {
        return static function ($ingressConfigs) use ($compiledDeployment): void {
            if (empty($ingressConfigs)) {
                return;
            }

            foreach ($ingressConfigs as $name => &$config) {
                $paths = [];
                foreach ($config[static::$keyIngressPaths] ?? [] as $path) {
                    $paths[] = new IngressPath(
                        $path[static::$keyIngressPath],
                        $path[static::$keyIngressService][static::$keyIngressServiceName] ?? null,
                        (int) ($path[static::$keyIngressService][static::$keyIngressPort] ?? null)
                    );
                }

                $port = $config[static::$keyIngressService][static::$keyIngressPort] ?? null;
                if (null !== $port) {
                    $port = (int) $port;
                }

                $compiledDeployment->addIngress(
                    $name,
                    new Ingress(
                        $name,
                        $config[static::$keyIngressHost],
                        $config[static::$keyIngressService][static::$keyIngressProvider] ?? null,
                        $config[static::$keyIngressService][static::$keyIngressServiceName] ?? null,
                        $port,
                        $paths,
                        $config[static::$keyIngressTls][static::$keyIngressSecret] ?? null
                    )
                );
            }
        };
    }
}
