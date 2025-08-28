<?php

declare(strict_types=1);

/*
* East Paas.
*
* LICENSE
*
* This source file is subject to the 3-Clause BSD license and the version 3 of the GPL3
* it is available in LICENSE file at the root of this package
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to richard@teknoo.software so we can send you a copy immediately.
*
*
* @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
* @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
*
* @link        https://teknoo.software/east-collection/paas Project website
*
* @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
* @author      Richard Déloge <richard@teknoo.software>
*/

namespace Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Psr11;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * Trait to perform PSR request to forward any jobs events to a remote server via a PSR Http Client.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
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
