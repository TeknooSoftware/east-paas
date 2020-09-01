<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Traits;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

trait RequestTrait
{
    private UriFactoryInterface $uriFactory;

    private RequestFactoryInterface $requestFactory;

    private StreamFactoryInterface $streamFactory;

    private ClientInterface $client;

    private function sendRequest(
        string $method,
        string $url,
        string $contentType,
        string $body
    ): void {
        $uri = $this->uriFactory->createUri($url);

        $request = $this->requestFactory->createRequest(
            $method,
            $uri
        );

        $stream = $this->streamFactory->createStream($body);

        $request = $request->withAddedHeader('content-type', $contentType);
        $request = $request->withBody($stream);

        $this->client->sendRequest($request);
    }
}
