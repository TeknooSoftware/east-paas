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

namespace Teknoo\Tests\East\Paas\Behat\Handler\Psr11;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Contracts\Messenger\Handler\HistorySentHandlerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Psr11\HistorySentHandler as OriginalHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\HistorySent;
use Teknoo\Tests\East\Paas\Behat\FeatureContext;

use function is_array;
use function json_decode;

/**
 * Message handler for Symfony Messenger to handle a HistorySent and forward it to a remmote server via a HTTP request.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

#[AsMessageHandler]
class HistorySentHandler extends OriginalHandler
{
    public function __invoke(HistorySent $historySent): HistorySentHandlerInterface
    {
        FeatureContext::$messageByTypeIsEncrypted[HistorySent::class] = !is_array(
                json_decode(
                    $historySent->getMessage(),
                    associative: true
                )
            )
            || (FeatureContext::$messageByTypeIsEncrypted[HistorySent::class] ?? false);

        parent::__invoke($historySent);

        return $this;
    }
}
