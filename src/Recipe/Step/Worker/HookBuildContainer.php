<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Step\Worker;

use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

class HookBuildContainer
{
    public function __invoke(JobWorkspaceInterface $workspace, CompiledDeployment $compiledDeployment): self
    {
        $workspace->runInRoot(
            static function ($path) use ($compiledDeployment) {
                $compiledDeployment->foreachHook(
                    static function (HookInterface $hook) use ($path) {
                        $hook->setPath($path);
                        $hook->run();
                    }
                );
            }
        );

        return $this;
    }
}
