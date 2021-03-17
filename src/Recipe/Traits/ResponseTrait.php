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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Traits;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teknoo\East\Foundation\Http\Message\MessageFactoryInterface;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
trait ResponseTrait
{
    private static function buildResponse(
        string $body,
        int $httpCode,
        string $contentType,
        MessageFactoryInterface $messageFactory,
        StreamFactoryInterface $streamFactory
    ): MessageInterface {
        $stream = $streamFactory->createStream($body);

        $message = $messageFactory->createMessage('1.1');

        if ($message instanceof ResponseInterface) {
            $message = $message->withStatus($httpCode);
        }

        $message = $message->withAddedHeader('paas-http-code', (string) $httpCode);
        $message = $message->withAddedHeader('content-type', $contentType);
        $message = $message->withBody($stream);

        return $message;
    }
}
