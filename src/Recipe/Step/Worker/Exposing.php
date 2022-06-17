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

namespace Teknoo\East\Paas\Recipe\Step\Worker;

use RuntimeException;
use Teknoo\East\Foundation\Client\ClientInterface as EastClient;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Paas\Cluster\Collection;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Throwable;

/**
 * Step to perform the exposition of containers previously deployed.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Exposing
{
    public function __construct(
        private readonly DispatchHistoryInterface $dispatchHistory,
    ) {
    }

    /**
     * @param Collection<DriverInterface> $clustersClients
     */
    public function __invoke(
        Collection $clustersClients,
        CompiledDeploymentInterface $compiledDeployment,
        EastClient $eastClient,
        ManagerInterface $manager,
        string $projectId,
        string $envName,
        JobUnitInterface $jobUnit
    ): self {
        /** @var Promise<array<string, mixed>, mixed, mixed> $promise */
        $promise = new Promise(
            function (array $result) use ($projectId, $envName, $jobUnit) {
                ($this->dispatchHistory)(
                    $projectId,
                    $envName,
                    $jobUnit->getId(),
                    self::class . ':Result',
                    $result
                );
            },
            fn (Throwable $error) => $manager->error(
                new RuntimeException(
                    'teknoo.east.paas.error.recipe.cluster.exposing_error',
                    500,
                    $error
                )
            )
        );

        /** @var DriverInterface $client */
        foreach ($clustersClients as $client) {
            $client->expose($compiledDeployment, $promise);
        }

        return $this;
    }
}
