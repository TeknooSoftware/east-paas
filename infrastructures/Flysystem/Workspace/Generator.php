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
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
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
