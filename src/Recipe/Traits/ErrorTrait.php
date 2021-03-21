<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
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

use Teknoo\East\Foundation\Http\Message\MessageFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Throwable;

use function json_encode;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
trait ErrorTrait
{
    use ResponseTrait;

    private static function buildFailurePromise(
        ClientInterface $client,
        ManagerInterface $manager,
        ?string $message,
        int $httpCode,
        MessageFactoryInterface $messageFactory,
        StreamFactoryInterface $streamFactory
    ): callable {
        return static function (Throwable $error) use (
            $client,
            $manager,
            $message,
            $httpCode,
            $messageFactory,
            $streamFactory
        ) {
            if (null === $message) {
                $message = $error->getMessage();
                $httpCode = $error->getCode();
            }

            $client->acceptResponse(
                self::buildResponse(
                    (string) json_encode(
                        [
                            'type' => 'https://teknoo.software/probs/issue',
                            'title' => $message,
                            'status' => $httpCode,
                            'detail' => $error->getMessage(),
                        ]
                    ),
                    $httpCode,
                    'application/problem+json',
                    $messageFactory,
                    $streamFactory
                )
            );

            $manager->finish($error);
        };
    }
}
