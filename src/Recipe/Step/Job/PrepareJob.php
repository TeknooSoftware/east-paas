<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Step\Job;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Website\Service\DatesService;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Paas\Recipe\Traits\ErrorTrait;
use Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait;

class PrepareJob
{
    use ErrorTrait;
    use PsrFactoryTrait;

    private DatesService $dateTimeService;

    public function __construct(
        DatesService $dateTimeService,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->dateTimeService = $dateTimeService;
        $this->setResponseFactory($responseFactory);
        $this->setStreamFactory($streamFactory);
    }

    public function __invoke(
        Project $project,
        Environment $environment,
        Job $job,
        ManagerInterface $manager,
        ClientInterface $client
    ): self {
        $responseFactory = $this->responseFactory;
        $streamFactory = $this->streamFactory;

        $this->dateTimeService->passMeTheDate(
            static function (\DateTimeInterface $now) use (
                $project,
                $environment,
                $job,
                $manager,
                $client,
                $responseFactory,
                $streamFactory
            ) {
                $project->prepareJob($job, $now, $environment);

                $job->isRunnable(
                    new Promise(
                        null,
                        static::buildFailurePromise(
                            $client,
                            $manager,
                            null,
                            500,
                            $responseFactory,
                            $streamFactory
                        )
                    )
                );
            }
        );

        return $this;
    }
}
