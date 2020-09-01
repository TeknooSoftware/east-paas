<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Traits;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;

trait ErrorTrait
{
    use ResponseTrait;

    private static function buildFailurePromise(
        ClientInterface $client,
        ManagerInterface $manager,
        ?string $message,
        int $httpCode,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory
    ): callable {
        return static function (\Throwable $error) use (
            $client,
            $manager,
            $message,
            $httpCode,
            $responseFactory,
            $streamFactory
        ) {
            if (null === $message) {
                $message = $error->getMessage();
                $httpCode = $error->getCode();
            }

            $client->acceptResponse(
                self::buildResponse(
                    (string) \json_encode(
                        [
                            'error' => true,
                            'message' => $message,
                            'extra' => $error->getMessage()
                        ]
                    ),
                    $httpCode,
                    'application/json',
                    $responseFactory,
                    $streamFactory
                )
            );

            $manager->finish($error);
        };
    }
}
