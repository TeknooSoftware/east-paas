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
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait;
use Teknoo\East\Paas\Recipe\Traits\ResponseTrait;
use Teknoo\Recipe\ChefInterface;

class DisplayJob
{
    use ResponseTrait;
    use PsrFactoryTrait;

    public function __construct(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory)
    {
        $this->setResponseFactory($responseFactory);
        $this->setStreamFactory($streamFactory);
    }

    public function __invoke(Job $job, ClientInterface $client, ChefInterface $chef, string $jobSerialized): self
    {
        $client->acceptResponse(
            static::buildResponse(
                $jobSerialized,
                200,
                'application/json',
                $this->responseFactory,
                $this->streamFactory
            )
        );

        $chef->finish($job);

        return $this;
    }
}
