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

namespace Teknoo\East\Paas\Infrastructures\EastPaasBundle\Subscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command\DisplayHistoryHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command\DisplayResultHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Forward\HistorySentHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Forward\JobDoneHandler;

/**
 * Event subscriber, on console's events to update handlers of HistorySent and JobDone message in Symfony Messenger on
 * dedicated console handler to print messge on the standard output.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/states Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class CommandSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly DisplayHistoryHandler $displayHistory,
        private readonly DisplayResultHandler $displayResult,
        private readonly HistorySentHandler $historyForwarder,
        private readonly JobDoneHandler $jobForwarder,
    ) {
    }


    /**
     * @return  array<string, array<int, array<int, string>>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => [
                ['updateForwarders']
            ]
        ];
    }

    public function updateForwarders(): self
    {
        $this->historyForwarder->setHandler($this->displayHistory);
        $this->jobForwarder->setHandler($this->displayResult);

        return $this;
    }
}
