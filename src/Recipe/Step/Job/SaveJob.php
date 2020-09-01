<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Step\Job;

use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Writer\JobWriter;
use Teknoo\Recipe\ChefInterface;

class SaveJob
{
    private JobWriter $jobWriter;

    public function __construct(JobWriter $jobWriter)
    {
        $this->jobWriter = $jobWriter;
    }

    public function __invoke(Job $job, ChefInterface $chef, ClientInterface $client): self
    {
        $this->jobWriter->save(
            $job,
            new Promise(
                null,
                static function (\Throwable $error) use ($client, $chef) {
                    $client->errorInRequest($error);

                    $chef->finish($error);
                }
            )
        );

        return $this;
    }
}
