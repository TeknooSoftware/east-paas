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
use Teknoo\East\Paas\Contracts\Serializing\DeserializerInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Paas\Recipe\Traits\ErrorTrait;
use Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait;

class DeserializeJob
{
    use ErrorTrait;
    use PsrFactoryTrait;

    private DeserializerInterface $deserializer;

    /**
     * @var array<string, mixed>
     */
    private array $variables;

    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(
        DeserializerInterface $deserializer,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        array $variables
    ) {
        $this->deserializer = $deserializer;
        $this->variables = $variables;
        $this->setResponseFactory($responseFactory);
        $this->setStreamFactory($streamFactory);
    }

    public function __invoke(string $serializedJob, ManagerInterface $manager, ClientInterface $client): self
    {
        $this->deserializer->deserialize(
            $serializedJob,
            JobUnitInterface::class,
            'json',
            new Promise(
                static function (JobUnitInterface $jobUnit) use ($manager) {
                    $manager->updateWorkPlan([JobUnitInterface::class => $jobUnit]);
                },
                static::buildFailurePromise(
                    $client,
                    $manager,
                    'teknoo.paas.error.recipe.job.mal_formed',
                    400,
                    $this->responseFactory,
                    $this->streamFactory
                )
            ),
            [
                'variables' => $this->variables
            ]
        );

        return $this;
    }
}
