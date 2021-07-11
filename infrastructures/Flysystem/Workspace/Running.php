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

namespace Teknoo\East\Paas\Infrastructures\Flysystem\Workspace;

use Closure;
use Teknoo\East\Paas\Infrastructures\Flysystem\Workspace;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * State for the class Workplan for the daughter instance present into the workplan.
 *
 * @mixin Workspace
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Running implements StateInterface
{
    use StateTrait;

    private function getJob(): Closure
    {
        return function (): JobUnitInterface {
            return $this->job;
        };
    }

    private function doClean(): Closure
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

    private function initFileSystem(): Closure
    {
        return function (): void {
            $workspacePath = $this->getWorkspacePath();

            $this->filesystem->createDir($workspacePath);
        };
    }

    private function getWorkspacePath(): Closure
    {
        return function (): string {
            $jobId = $this->getJob()->getId() . $this->getRand();

            return '/' . $jobId . '/';
        };
    }

    private function getRepositoryPath(): Closure
    {
        return function (): string {
            return $this->getWorkspacePath() . 'repository/';
        };
    }
}
