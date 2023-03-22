<?php

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

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Forward;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Contracts\Messenger\Handler\HistorySentHandlerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\HistorySent;

/**
 * Default message handler for Symfony Messenger to handle a HistorySent and forward it to the defined handler.
 * If there are no defined handler, the message will be ignored, without throw any error.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
#[AsMessageHandler]
class HistorySentHandler
{
    private ?HistorySentHandlerInterface $handler = null;

    public function setHandler(?HistorySentHandlerInterface $handler): self
    {
        $this->handler = $handler;

        return $this;
    }

    public function __invoke(HistorySent $historySent): self
    {
        if (null !== $this->handler) {
            ($this->handler)($historySent);
        }

        return $this;
    }
}
