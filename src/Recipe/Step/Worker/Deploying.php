<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
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
