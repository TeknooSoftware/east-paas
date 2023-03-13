<?php

declare(strict_types=1);

/*
* East Paas.
*
* LICENSE
*
* This source file is subject to the MIT license and the version 3 of the GPL3
* license that are bundled with this package in the folder licences
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to richarddeloge@gmail.com so we can send you a copy immediately.
*
*
* @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
* @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
*
* @link        http://teknoo.software/east/paas Project website
*
* @license     http://teknoo.software/license/mit         MIT License
* @author      Richard Déloge <richarddeloge@gmail.com>
*/

namespace Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Psr11;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * Trait to perform PSR request to forward any jobs events to a remote server via a PSR Http Client.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/states Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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
