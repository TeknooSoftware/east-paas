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

use DomainException;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\Recipe\ChefInterface;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Paas\Loader\JobLoader;
use Teknoo\East\Paas\Object\Job;
use Throwable;

/**
 * Step to load a persisted job from the DB source thanks to the job loaded and inject it into the workplan.
 * On any error, the error factory will be called.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/states Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class GetJob
{
    public function __construct(
        private readonly JobLoader $jobLoader,
    ) {
    }

    public function __invoke(string $jobId, ManagerInterface $manager, ClientInterface $client): self
    {
        /** @var Promise<Job, mixed, mixed> $fetchedPromise */
        $fetchedPromise = new Promise(
            static function (Job $job) use ($manager): void {
                $manager->updateWorkPlan([Job::class => $job]);
            },
            static fn(Throwable $error): ChefInterface => $manager->error(
                throw new DomainException(
                    'teknoo.east.paas.error.recipe.job.not_found',
                    404,
                    $error
                )
            )
        );

        $this->jobLoader->load(
            $jobId,
            $fetchedPromise
        );

        return $this;
    }
}
