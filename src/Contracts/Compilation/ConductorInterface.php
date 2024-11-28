<?php

declare(strict_types=1);

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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Contracts\Compilation;

use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * To define service able to validate and prepare a deployment by compiling instructions from paas.yaml to objects
 * understable by deployments adapters and clusters's drivers, grouped into a summary object implemented via
 * 'CompiledDeploymentInterface'.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
interface ConductorInterface
{
    public function configure(
        JobUnitInterface $job,
        JobWorkspaceInterface $workspace
    ): ConductorInterface;

    /**
     * @param PromiseInterface<string, mixed> $promise
     */
    public function prepare(
        string $configuration,
        PromiseInterface $promise
    ): ConductorInterface;

    /**
     * @param PromiseInterface<CompiledDeploymentInterface, mixed> $promise
     */
    public function compileDeployment(
        PromiseInterface $promise,
    ): ConductorInterface;
}
