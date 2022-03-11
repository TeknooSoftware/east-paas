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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Contracts\Workspace;

use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\East\Paas\Contracts\Compilation\ConductorInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;

/**
 * To represent the dedicated file system manager used locally to perform the deployment, clone source,
 s* prepare deployment (get vendors, compile, do some stuf, etc...) compile oci images
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
interface JobWorkspaceInterface extends ImmutableInterface
{
    final public const CONFIGURATION_FILE = '.paas.yml';

    public function setJob(JobUnitInterface $job): JobWorkspaceInterface;

    public function clean(): JobWorkspaceInterface;

    public function writeFile(FileInterface $file, callable $return = null): JobWorkspaceInterface;

    public function prepareRepository(CloningAgentInterface $cloningAgent): JobWorkspaceInterface;

    /**
     * @param PromiseInterface<string, mixed> $promise
     */
    public function loadDeploymentIntoConductor(
        ConductorInterface $conductor,
        PromiseInterface $promise
    ): JobWorkspaceInterface;

    /**
     * @param PromiseInterface<mixed, mixed> $promise
     */
    public function hasDirectory(string $path, PromiseInterface $promise): JobWorkspaceInterface;

    public function runInRoot(callable $callback): JobWorkspaceInterface;
}
