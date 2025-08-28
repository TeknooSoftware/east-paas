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

namespace Teknoo\East\Paas\Recipe\Step\Job;

use DateTimeInterface;
use SensitiveParameter;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\East\Paas\Contracts\Response\ErrorFactoryInterface;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\Recipe\Promise\Promise;

/**
 * Step to prepare a new deployment of a project.
 * On any error, the error factory will be called.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
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
        #[SensitiveParameter] Project $project,
        Environment $environment,
        #[SensitiveParameter] Job $job,
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
            ): void {
                $project->prepareJob($job, $now, $environment);

                $job->isRunnable(
                    new Promise(
                        onFail: $errorFactory->buildFailureHandler(
                            $client,
                            $manager,
                            500,
                            null,
                        ),
                    )
                );
            },
            $this->preferRealDate,
        );

        return $this;
    }
}
