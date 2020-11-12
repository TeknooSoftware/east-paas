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

namespace Teknoo\East\Paas\Conductor\Conductor;

use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Conductor\Conductor;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * @mixin Conductor
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Running implements StateInterface
{
    use StateTrait;

    private function getJob(): \Closure
    {
        return function (): JobUnitInterface {
            return $this->job;
        };
    }

    private function getWorkspace(): \Closure
    {
        return function (): JobWorkspaceInterface {
            return $this->workspace;
        };
    }

    private function extractAndCompile(): \Closure
    {
        return function (CompiledDeployment $compiledDeployment): void {
            $this->extract(
                $this->configuration,
                static::CONFIG_VOLUMES,
                [],
                $this->compileVolumes($compiledDeployment, $this->getJob()->getId())
            );

            $this->extract(
                $this->configuration,
                static::CONFIG_IMAGES,
                [],
                $this->compileImages($this->imagesLibrary, $compiledDeployment, $this->getWorkspace())
            );

            $this->extract(
                $this->configuration,
                static::CONFIG_BUILDS,
                [],
                $this->compileHooks($this->hooksLibrary, $compiledDeployment)
            );

            $this->extract(
                $this->configuration,
                static::CONFIG_PODS,
                [],
                $this->compilePods($compiledDeployment)
            );

            $this->extract(
                $this->configuration,
                static::CONFIG_SERVICES,
                [],
                $this->compileServices($compiledDeployment)
            );
        };
    }
}
