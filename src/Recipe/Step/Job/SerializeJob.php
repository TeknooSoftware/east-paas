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
use Teknoo\East\Paas\Contracts\Serializing\SerializerInterface;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Paas\Recipe\Traits\ErrorTrait;
use Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait;

class SerializeJob
{
    use ErrorTrait;
    use PsrFactoryTrait;

    private SerializerInterface $serializer;

    public function __construct(
        SerializerInterface $serializer,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->serializer = $serializer;
        $this->setResponseFactory($responseFactory);
        $this->setStreamFactory($streamFactory);
    }

    /**
     * @param array<string, mixed> $envVars
     */
    public function __invoke(Job $job, ManagerInterface $manager, ClientInterface $client, array $envVars = []): self
    {
        $this->serializer->serialize(
            $job,
            'json',
            new Promise(
                static function (string $jobSerialized) use ($manager) {
                    $manager->updateWorkPlan(['jobSerialized' => $jobSerialized]);
                },
                static::buildFailurePromise(
                    $client,
                    $manager,
                    'teknoo.paas.error.recipe.job.serialization_error',
                    400,
                    $this->responseFactory,
                    $this->streamFactory
                )
            ),
            [
                'add' => [
                    'variables' => $envVars,
                ],
            ]
        );



        return $this;
    }
}
