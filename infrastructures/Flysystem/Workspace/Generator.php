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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Flysystem\Workspace;

use Closure;
use RuntimeException;
use Teknoo\East\Paas\Infrastructures\Flysystem\Workspace;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * State for the class Workspace for the mother instance present into container, to build new Conductor instance via
 * a self cloning.
 *
 * @mixin Workspace
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Generator implements StateInterface
{
    use StateTrait;

    private function doClean(): Closure
    {
        return function (): void {
        };
    }

    private function getWorkspacePath(): Closure
    {
        return function (): string {
            throw new RuntimeException('Workspace is in generator state');
        };
    }

    private function getRepositoryPath(): Closure
    {
        return function (): string {
            throw new RuntimeException('Workspace is in generator state');
        };
    }
}
