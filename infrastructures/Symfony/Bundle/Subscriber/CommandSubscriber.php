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

namespace Teknoo\East\Paas\Infrastructures\EastPaasBundle\Subscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command\DisplayHistoryHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command\DisplayResultHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Forward\HistorySentHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Forward\JobDoneHandler;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class CommandSubscriber implements EventSubscriberInterface
{
    private DisplayHistoryHandler $historyHandler;

    private DisplayResultHandler $resultHandler;

    private HistorySentHandler $historyForwarder;

    private JobDoneHandler $jobForwarder;

    public function __construct(
        DisplayHistoryHandler $historyHandler,
        DisplayResultHandler $resultHandler,
        HistorySentHandler $historyForwarder,
        JobDoneHandler $jobForwarder
    ) {
        $this->historyHandler = $historyHandler;
        $this->resultHandler = $resultHandler;
        $this->historyForwarder = $historyForwarder;
        $this->jobForwarder = $jobForwarder;
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
        $this->historyForwarder->setHandler($this->historyHandler);
        $this->jobForwarder->setHandler($this->resultHandler);

        return $this;
    }
}
