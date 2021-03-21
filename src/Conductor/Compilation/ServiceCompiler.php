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

namespace Teknoo\East\Paas\Conductor\Compilation;

use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;
use Teknoo\East\Paas\Container\Expose\Service;
use Teknoo\East\Paas\Contracts\Conductor\CompilerInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ServiceCompiler implements CompilerInterface
{
    private const KEY_PORTS = 'ports';
    private const KEY_LISTEN = 'listen';
    private const KEY_TARGET = 'target';
    private const KEY_POD_NAME = 'pod';
    private const KEY_PROTOCOL = 'protocol';
    private const KEY_INTERNAL = 'internal';

    public function compile(
        array &$definitions,
        CompiledDeploymentInterface $compiledDeployment,
        JobWorkspaceInterface $workspace,
        JobUnitInterface $job,
        ?string $storageIdentifier = null
    ): CompilerInterface {
        foreach ($definitions as $name => &$config) {
            $ports = [];
            foreach ($config[static::KEY_PORTS] as $row) {
                $ports[(int) $row[static::KEY_LISTEN]] = (int) $row[static::KEY_TARGET];
            }

            $compiledDeployment->addService(
                $name,
                new Service(
                    $name,
                    $config[static::KEY_POD_NAME] ?? $name,
                    $ports,
                    $config[static::KEY_PROTOCOL] ?? Service::TCP,
                    !empty($config[static::KEY_INTERNAL])
                )
            );
        }

        return $this;
    }
}
