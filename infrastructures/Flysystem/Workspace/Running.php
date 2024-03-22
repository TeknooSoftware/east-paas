<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

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
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
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

            $this->filesystem->deleteDirectory($workspacePath);

            $this->job = null;
            $this->updateStates();
        };
    }

    private function initFileSystem(): Closure
    {
        return function (): void {
            $workspacePath = $this->getWorkspacePath();

            $this->filesystem->createDirectory($workspacePath);
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
            return 'repository/';
        };
    }
}
