<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
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
