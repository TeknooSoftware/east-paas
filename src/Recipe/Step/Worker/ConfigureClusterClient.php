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
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Throwable;

/**
 * Step to configure the cluster client with job's configuration and push it into the workplan.
 * (The client injected is a clone of the original client, the original is "immutable").
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ConfigureClusterClient
{
    public function __construct(
        private Directory $clientsDirectory,
    ) {
    }

    public function __invoke(
        JobUnitInterface $job,
        EastClient $eastClient,
        ManagerInterface $manager
    ): self {
        /** @var Promise<Collection, mixed, mixed> $promise */
        $promise = new Promise(
            static function (Collection $clients) use ($manager) {
                $manager->updateWorkPlan(
                    [
                        Collection::class => $clients
                    ]
                );
            },
            fn (Throwable $error) => throw new RuntimeException(
                'teknoo.east.paas.error.recipe.cluster.configuration_error',
                500,
                $error
            )
        );

        $job->configureCluster($this->clientsDirectory, $promise);

        return $this;
    }
}
