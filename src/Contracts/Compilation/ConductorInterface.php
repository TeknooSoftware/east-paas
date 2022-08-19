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

namespace Teknoo\East\Paas\Contracts\Compilation;

use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * To define service able to validate and prepare a deployment by compiling instructions from paas.yaml to objects
 * understable by deployments adapters and clusters's drivers, grouped into a summary object implemented via
 * 'CompiledDeploymentInterface'.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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
        ?string $storageIdentifier = null,
        ?string $storageSize = null,
        ?string $defaultOciRegistryConfig = null,
    ): ConductorInterface;
}
