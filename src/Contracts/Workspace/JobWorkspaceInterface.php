<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
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

namespace Teknoo\East\Paas\Contracts\Workspace;

use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\East\Paas\Contracts\Conductor\ConductorInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
interface JobWorkspaceInterface extends ImmutableInterface
{
    public const CONFIGURATION_FILE = '.paas.yml';

    public function setJob(JobUnitInterface $job): JobWorkspaceInterface;

    public function clean(): JobWorkspaceInterface;

    public function writeFile(FileInterface $file, callable $return = null): JobWorkspaceInterface;

    public function prepareRepository(CloningAgentInterface $cloningAgent): JobWorkspaceInterface;

    public function loadDeploymentIntoConductor(
        ConductorInterface $conductor,
        PromiseInterface $promise
    ): JobWorkspaceInterface;

    public function hasDirectory(string $path, PromiseInterface $promise): JobWorkspaceInterface;

    public function runInRoot(callable $callback): JobWorkspaceInterface;
}
