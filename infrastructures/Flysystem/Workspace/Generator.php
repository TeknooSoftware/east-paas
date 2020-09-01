<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Flysystem\Workspace;

use Teknoo\East\Paas\Infrastructures\Flysystem\Workspace;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * @mixin Workspace
 */
class Generator implements StateInterface
{
    use StateTrait;

    private function getJob(): \Closure
    {
        return function (): JobUnitInterface {
            throw new \RuntimeException('Workspace is in generator state');
        };
    }

    private function initFileSystem(): \Closure
    {
        return function (): void {
            throw new \RuntimeException('Workspace is in generator state');
        };
    }

    private function doClean(): \Closure
    {
        return function (): void {
        };
    }

    private function getWorkspacePath(): \Closure
    {
        return function (): string {
            throw new \RuntimeException('Workspace is in generator state');
        };
    }

    private function getRepositoryPath(): \Closure
    {
        return function (): string {
            throw new \RuntimeException('Workspace is in generator state');
        };
    }
}
