<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Conductor\Conductor;

use Teknoo\East\Paas\Conductor\Conductor;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * @mixin Conductor
 */
class Generator implements StateInterface
{
    use StateTrait;

    private function getJob(): \Closure
    {
        return function (): JobUnitInterface {
            throw new \RuntimeException('Conductor is in generator state');
        };
    }

    private function getWorkspace(): \Closure
    {
        return function (): JobWorkspaceInterface {
            throw new \RuntimeException('Workspace is in generator state');
        };
    }
}
