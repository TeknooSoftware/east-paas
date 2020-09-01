<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Traits;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

trait ResponseTrait
{
    private static function buildResponse(
        string $body,
        int $httpCode,
        string $contentType,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory
    ): ResponseInterface {
        $stream = $streamFactory->createStream($body);

        $response = $responseFactory->createResponse($httpCode);
        $response = $response->withAddedHeader('content-type', $contentType);
        $response = $response->withBody($stream);

        return $response;
    }
}
