<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Recipe\Step\Worker;

use RuntimeException;
use SensitiveParameter;
use Teknoo\East\Foundation\Client\ClientInterface as EastClient;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\Recipe\ChefInterface;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface;
use Teknoo\East\Paas\Cluster\Collection;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Throwable;

/**
 * Step to perform the deployment of container from built images in previous step on the cluster thanks to
 * the cluster client.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Deploying
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
            onSuccess: function (array $result) use ($projectId, $envName, $jobUnit): void {
                /** @var array<string, mixed> $result */
                ($this->dispatchHistory)(
                    $projectId,
                    $envName,
                    $jobUnit->getId(),
                    self::class . ':Result',
                    $result,
                );
            },
            onFail: static fn (#[SensitiveParameter] Throwable $error): ChefInterface => $manager->error(
                new RuntimeException(
                    'teknoo.east.paas.error.recipe.cluster.deployment_error',
                    500,
                    $error,
                ),
            ),
        );

        $promise->allowReuse();

        /** @var DriverInterface $client */
        foreach ($clustersClients as $client) {
            $client->deploy($compiledDeployment, $promise);
        }

        return $this;
    }
}
