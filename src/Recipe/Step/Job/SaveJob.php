<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Recipe\Step\Job;

use RuntimeException;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Writer\JobWriter;
use Teknoo\Recipe\ChefInterface;
use Throwable;

/**
 * Step to persist a job object into the database.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class SaveJob
{
    public function __construct(
        private readonly JobWriter $jobWriter,
    ) {
    }

    public function __invoke(Job $job, ChefInterface $chef, ClientInterface $client): self
    {
        /** @var Promise<Job, mixed, mixed> $savedPromise */
        $savedPromise = new Promise(
            null,
            static fn (Throwable $error): ChefInterface => $chef->error(
                new RuntimeException(
                    message: 'teknoo.east.paas.job.save_error',
                    code: 500,
                    previous: $error,
                )
            ),
        );

        $this->jobWriter->save(
            $job,
            $savedPromise
        );

        return $this;
    }
}
