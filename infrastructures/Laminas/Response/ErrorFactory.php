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

namespace Teknoo\East\Paas\Infrastructures\Laminas\Response;

use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Response\ErrorFactoryInterface;
use Throwable;

/**
 * Factory able to create an `ErrorInterface` instance and pass it to the client
 * and finish the recipe on the manager.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ErrorFactory implements ErrorFactoryInterface
{
    public function buildFailurePromise(
        ClientInterface $client,
        ManagerInterface $manager,
        int $statusCode,
        ?string $reasonPhrase,
    ): callable {
        return static function (Throwable $error) use (
            $client,
            $manager,
            $statusCode,
            $reasonPhrase,
        ): void {
            if (null === $reasonPhrase) {
                $reasonPhrase = $error->getMessage();
                $statusCode = $error->getCode();
            }

            if ($statusCode < 400 || $statusCode > 600) {
                $statusCode = 500;
            }

            $client->acceptResponse(
                new Error($statusCode, (string) $reasonPhrase, $error)
            );

            $manager->finish($error);
        };
    }
}
