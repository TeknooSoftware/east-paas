<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
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
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Infrastructures\Laminas\Response;

use SensitiveParameter;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Response\ErrorFactoryInterface;
use Throwable;

/**
 * Factory able to create an `ErrorInterface` instance and pass it to the client
 * and finish the recipe on the manager.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ErrorFactory implements ErrorFactoryInterface
{
    public function buildFailureHandler(
        ClientInterface $client,
        ManagerInterface $manager,
        int $statusCode,
        ?string $reasonPhrase,
    ): callable {
        return static function (#[SensitiveParameter] Throwable $error) use (
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
