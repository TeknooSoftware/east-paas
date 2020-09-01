<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Step\History;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait;
use Teknoo\East\Paas\Recipe\Traits\ResponseTrait;
use Teknoo\Recipe\ChefInterface;

class DisplayHistory
{
    use ResponseTrait;
    use PsrFactoryTrait;

    public function __construct(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory)
    {
        $this->setResponseFactory($responseFactory);
        $this->setStreamFactory($streamFactory);
    }

    public function __invoke(
        History $history,
        ClientInterface $client,
        ChefInterface $chef,
        string $historySerialized
    ): self {
        $client->acceptResponse(
            static::buildResponse(
                $historySerialized,
                200,
                'application/json',
                $this->responseFactory,
                $this->streamFactory
            )
        );

        $chef->finish($history);

        return $this;
    }
}
