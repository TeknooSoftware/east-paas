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
class Running implements StateInterface
{
    use StateTrait;

    private function getJob(): \Closure
    {
        return function (): JobUnitInterface {
            return $this->job;
        };
    }

    private function doClean(): \Closure
    {
        return function (): void {
            $workspacePath = $this->getWorkspacePath();

            if ($this->filesystem->has($workspacePath)) {
                $this->filesystem->deleteDir($workspacePath);
            }

            $this->job = null;
            $this->updateStates();
        };
    }

    private function initFileSystem(): \Closure
    {
        return function (): void {
            $workspacePath = $this->getWorkspacePath();

            $this->filesystem->createDir($workspacePath);
        };
    }

    private function getWorkspacePath(): \Closure
    {
        return function (): string {
            $jobId = $this->getJob()->getId() . $this->getRand();

            return '/' . $jobId . '/';
        };
    }

    private function getRepositoryPath(): \Closure
    {
        return function (): string {
            return $this->getWorkspacePath() . 'repository/';
        };
    }
}
