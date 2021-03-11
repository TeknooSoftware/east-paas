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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
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

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
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
