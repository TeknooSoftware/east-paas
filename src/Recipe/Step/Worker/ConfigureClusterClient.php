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

namespace Teknoo\East\Paas\Recipe\Step\Worker;

use RuntimeException;
use Teknoo\East\Foundation\Client\ClientInterface as EastClient;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\Recipe\ChefInterface;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Paas\Cluster\Collection;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Throwable;

/**
 * Step to configure the cluster client with job's configuration and push it into the workplan.
 * (The client injected is a clone of the original client, the original is "immutable").
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ConfigureClusterClient
{
    public function __construct(
        private readonly Directory $clientsDirectory,
    ) {
    }

    public function __invoke(
        JobUnitInterface $job,
        EastClient $eastClient,
        ManagerInterface $manager,
        CompiledDeploymentInterface $compiledDeployment,
    ): self {
        /** @var Promise<Collection, mixed, mixed> $promise */
        $promise = new Promise(
            onSuccess: static function (Collection $clients) use ($manager): void {
                $manager->updateWorkPlan(
                    [
                        Collection::class => $clients
                    ]
                );
            },
            onFail: static fn(Throwable $error): ChefInterface => $manager->error(
                new RuntimeException(
                    'teknoo.east.paas.error.recipe.cluster.configuration_error',
                    500,
                    $error,
                ),
            ),
        );

        $job->configureCluster($this->clientsDirectory, $promise, $compiledDeployment);

        return $this;
    }
}
