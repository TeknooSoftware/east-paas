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

namespace Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Forward;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Contracts\Messenger\Handler\HistorySentHandlerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\HistorySent;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class HistorySentHandler implements MessageHandlerInterface
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
