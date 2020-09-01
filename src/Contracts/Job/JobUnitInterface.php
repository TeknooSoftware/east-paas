<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Contracts\Job;

use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\East\Paas\Contracts\Cluster\ClientInterface as ClusterClientInterface;
use Teknoo\East\Paas\Contracts\Container\BuilderInterface as ImageBuilder;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;

interface JobUnitInterface extends NormalizableInterface
{
    public function getId(): string;

    public function configureCloningAgent(
        CloningAgentInterface $agent,
        JobWorkspaceInterface $workspace,
        PromiseInterface $promise
    ): JobUnitInterface;

    public function configureImageBuilder(
        ImageBuilder $builder,
        PromiseInterface $promise
    ): JobUnitInterface;

    public function configureCluster(
        ClusterClientInterface $builder,
        PromiseInterface $promise
    ): JobUnitInterface;

    public function prepareUrl(string $url, PromiseInterface $promise): JobUnitInterface;

    /**
     * @param array<string, mixed> $values
     */
    public function updateVariablesIn(
        array $values,
        PromiseInterface $promise
    ): JobUnitInterface;
}
