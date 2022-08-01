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

namespace Teknoo\East\Paas\Recipe\Step\Job;

use DateTimeInterface;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Response\ErrorFactoryInterface;
use Teknoo\East\Common\Service\DatesService;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\Recipe\Promise\Promise;

/**
 * Step to prepare a new deployment of a project.
 * On any error, the error factory will be called.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class PrepareJob
{
    public function __construct(
        private readonly DatesService $dateTimeService,
        private readonly ErrorFactoryInterface $errorFactory,
        private readonly bool $preferRealDate = false,
    ) {
    }

    public function __invoke(
        Project $project,
        Environment $environment,
        Job $job,
        ManagerInterface $manager,
        ClientInterface $client
    ): self {
        $errorFactory = $this->errorFactory;

        $this->dateTimeService->passMeTheDate(
            static function (DateTimeInterface $now) use (
                $project,
                $environment,
                $job,
                $manager,
                $client,
                $errorFactory,
            ) {
                $project->prepareJob($job, $now, $environment);

                $job->isRunnable(
                    new Promise(
                        null,
                        $errorFactory->buildFailureHandler(
                            $client,
                            $manager,
                            500,
                            null,
                        )
                    )
                );
            },
            $this->preferRealDate,
        );

        return $this;
    }
}
