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

namespace Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Contracts\Messenger\Handler\HistorySentHandlerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\HistorySent;

/**
 * Message handler for Symfony Messenger to handle a HistorySent and print it to the standard output, in a
 * Symfony Console context
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/states Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class DisplayHistoryHandler implements HistorySentHandlerInterface
{
    private ?OutputInterface $output = null;

    public function setOutput(?OutputInterface $output): self
    {
        $this->output = $output;

        return $this;
    }

    public function __invoke(HistorySent $historySent): HistorySentHandlerInterface
    {
        if (null !== $this->output) {
            $this->output->writeln($historySent->getMessage());
        }

        return $this;
    }
}
