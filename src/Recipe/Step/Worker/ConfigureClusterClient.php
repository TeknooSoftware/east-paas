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
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Recipe\Traits\ErrorTrait;
use Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait;

class ConfigureClusterClient
{
    use ErrorTrait;
    use PsrFactoryTrait;

    private ClientInterface $client;

    public function __construct(
        ClientInterface $client,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->client = $client;
        $this->setResponseFactory($responseFactory);
        $this->setStreamFactory($streamFactory);
    }

    public function __invoke(
        JobUnitInterface $job,
        EastClient $eastClient,
        ManagerInterface $manager
    ): self {
        $job->configureCluster(
            $this->client,
            new Promise(
                static function (Collection $clients) use ($manager) {
                    $manager->updateWorkPlan(
                        [
                            Collection::class => $clients
                        ]
                    );
                },
                static::buildFailurePromise(
                    $eastClient,
                    $manager,
                    'teknoo.paas.error.recipe.cluster.configuration_error',
                    500,
                    $this->responseFactory,
                    $this->streamFactory
                )
            )
        );

        return $this;
    }
}
