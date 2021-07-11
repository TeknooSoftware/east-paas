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

namespace Teknoo\East\Paas\Recipe\Step\Worker;

use Teknoo\East\Foundation\Client\ClientInterface as EastClient;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Paas\Cluster\Collection;
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Contracts\Response\ErrorFactoryInterface;

/**
 * Step to perform the exposition of containers previously deployed.
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Exposing
{
    public function __construct(
        private DispatchHistoryInterface $dispatchHistory,
        private ErrorFactoryInterface $errorFactory,
    ) {
    }

    public function __invoke(
        Collection $clustersClients,
        CompiledDeploymentInterface $compiledDeployment,
        EastClient $eastClient,
        ManagerInterface $manager,
        string $projectId,
        string $envName,
        JobUnitInterface $jobUnit
    ): self {
        /** @var DriverInterface $client */
        foreach ($clustersClients as $client) {
            $client->expose(
                $compiledDeployment,
                new Promise(
                    function (array $result) use ($projectId, $envName, $jobUnit) {
                        ($this->dispatchHistory)(
                            $projectId,
                            $envName,
                            $jobUnit->getId(),
                            static::class . ':Result',
                            $result
                        );
                    },
                    $this->errorFactory->buildFailurePromise(
                        $eastClient,
                        $manager,
                        500,
                        'teknoo.east.paas.error.recipe.cluster.exposing_error',
                    )
                )
            );
        }

        return $this;
    }
}
