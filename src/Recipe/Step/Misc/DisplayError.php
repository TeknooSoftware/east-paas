<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Step\Misc;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Paas\Contracts\Serializing\SerializerInterface;
use Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait;
use Teknoo\East\Paas\Recipe\Traits\ResponseTrait;
use Teknoo\Recipe\ChefInterface;
use Teknoo\East\Foundation\Promise\Promise;

class DisplayError
{
    use ResponseTrait;
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

    public function __invoke(
        ClientInterface $client,
        ChefInterface $chef,
        \Throwable $throwable
    ): self {
        $this->serializer->serialize(
            $throwable,
            'json',
            new Promise(
                function (string $error) use ($client, $chef, $throwable) {
                    $client->acceptResponse(
                        static::buildResponse(
                            $error,
                            200,
                            'application/json',
                            $this->responseFactory,
                            $this->streamFactory
                        )
                    );

                    $chef->finish($throwable);
                }
            )
        );

        return $this;
    }
}
