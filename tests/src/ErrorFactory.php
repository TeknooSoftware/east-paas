<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
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

namespace Teknoo\Tests\East\Paas;

use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Client\ResponseInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Response\ErrorFactoryInterface;
use Throwable;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
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
        return static function (Throwable $error) use (
            $client,
            $manager,
        ): void {
            $client->acceptResponse(
                new class () implements ResponseInterface {
                    public function __toString(): string
                    {
                        return 'error';
                    }
                }
            );

            $manager->finish($error);
        };
    }
}
