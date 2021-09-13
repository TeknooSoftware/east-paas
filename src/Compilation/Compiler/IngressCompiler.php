<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
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

namespace Teknoo\East\Paas\Compilation\Compiler;

use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Ingress;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\IngressPath;
use Teknoo\East\Paas\Contracts\Compilation\CompilerInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * Compilation module able to convert `ingresses` sections in paas.yaml file as Ingress instance.
 * The Ingress instance will be pushed into the CompiledDeploymentInterface instance.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class IngressCompiler implements CompilerInterface
{
    private const KEY_HOST = 'host';
    private const KEY_TLS = 'tls';
    private const KEY_PROVIDER = 'provider';
    private const KEY_SECRET = 'secret';
    private const KEY_SERVICE = 'service';
    private const KEY_SERVICE_NAME = 'name';
    private const KEY_PORT = 'port';
    private const KEY_PATHS = 'paths';
    private const KEY_PATH = 'path';

    public function compile(
        array &$definitions,
        CompiledDeploymentInterface $compiledDeployment,
        JobWorkspaceInterface $workspace,
        JobUnitInterface $job,
        ?string $storageIdentifier = null
    ): CompilerInterface {
        foreach ($definitions as $name => &$config) {
            $paths = [];
            foreach ($config[static::KEY_PATHS] ?? [] as $path) {
                $paths[] = new IngressPath(
                    $path[static::KEY_PATH],
                    $path[static::KEY_SERVICE][static::KEY_SERVICE_NAME] ?? null,
                    (int) ($path[static::KEY_SERVICE][static::KEY_PORT] ?? null)
                );
            }

            $port = $config[static::KEY_SERVICE][static::KEY_PORT] ?? null;
            if (null !== $port) {
                $port = (int) $port;
            }

            $compiledDeployment->addIngress(
                $name,
                new Ingress(
                    $name,
                    $config[static::KEY_HOST],
                    $config[static::KEY_SERVICE][static::KEY_PROVIDER] ?? null,
                    $config[static::KEY_SERVICE][static::KEY_SERVICE_NAME] ?? null,
                    $port,
                    $paths,
                    $config[static::KEY_TLS][static::KEY_SECRET] ?? null
                )
            );
        }

        return $this;
    }
}
