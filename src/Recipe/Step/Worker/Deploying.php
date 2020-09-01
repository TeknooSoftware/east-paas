<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Step\Worker;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teknoo\East\Foundation\Http\ClientInterface as EastClient;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Paas\Contracts\Cluster\ClientInterface;
use Teknoo\East\Paas\Cluster\Collection;
use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Recipe\Step\History\SendHistory;
use Teknoo\East\Paas\Recipe\Traits\ErrorTrait;
use Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Deploying
{
    use ErrorTrait;
    use PsrFactoryTrait;

    private SendHistory $sendHistory;

    public function __construct(
        SendHistory $sendHistory,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->sendHistory = $sendHistory;
        $this->setResponseFactory($responseFactory);
        $this->setStreamFactory($streamFactory);
    }

    public function __invoke(
        Collection $clustersClients,
        CompiledDeployment $compiledDeployment,
        EastClient $eastClient,
        ManagerInterface $manager,
        JobUnitInterface $jobUnit
    ): self {
        /** @var ClientInterface $client */
        foreach ($clustersClients as $client) {
            $client->deploy(
                $compiledDeployment,
                new Promise(
                    function (array $result) use ($jobUnit) {
                        ($this->sendHistory)(
                            $jobUnit,
                            static::class . ':Result',
                            $result
                        );
                    },
                    static::buildFailurePromise(
                        $eastClient,
                        $manager,
                        'teknoo.paas.error.recipe.cluster.deployment_error',
                        500,
                        $this->responseFactory,
                        $this->streamFactory
                    )
                )
            );
        }

        return $this;
    }
}
