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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Contracts\Job;

use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\East\Paas\Cluster\Collection as ClusterCollection;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Compilation\Compiler\Quota\Factory as QuotaFactory;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuilderInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuilderInterface as ImageBuilder;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\Quota\AvailabilityInterface;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * Interface to define unit representing the current deployment execution' called a job.
 * This is a projection of the persisted object Job, dedicated to the execution.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
interface JobUnitInterface extends NormalizableInterface
{
    public function getId(): string;

    public function getShortId(): string;

    public function getEnvironmentTag(): string;

    public function getProjectNormalizedName(): string;

    /**
     * @param PromiseInterface<CloningAgentInterface, mixed> $promise
     */
    public function configureCloningAgent(
        CloningAgentInterface $agent,
        JobWorkspaceInterface $workspace,
        PromiseInterface $promise
    ): JobUnitInterface;

    /**
     * @param PromiseInterface<BuilderInterface, mixed> $promise
     */
    public function configureImageBuilder(
        ImageBuilder $builder,
        PromiseInterface $promise
    ): JobUnitInterface;

    /**
     * @param PromiseInterface<ClusterCollection, mixed> $promise
     */
    public function configureCluster(
        Directory $clientsDirectory,
        PromiseInterface $promise,
        CompiledDeploymentInterface $compiledDeployment,
    ): JobUnitInterface;

    /**
     * @param array<string, mixed> $values
     * @param PromiseInterface<array<string, mixed>, mixed> $promise
     */
    public function updateVariablesIn(
        array $values,
        PromiseInterface $promise
    ): JobUnitInterface;

    public function runWithExtra(callable $callback): JobUnitInterface;

    /**
     * @param PromiseInterface<array<string, AvailabilityInterface>, mixed> $promise
     */
    public function prepareQuotas(QuotaFactory $factory, PromiseInterface $promise): JobUnitInterface;
}
