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

namespace Teknoo\East\Paas\Infrastructures\EastPaasBundle\Listener;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Misc\DispatchResultInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Command\Steps\DisplayHistory;
use Teknoo\East\Paas\Infrastructures\Symfony\Command\Steps\DisplayResult;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class CommandListener implements EventSubscriberInterface
{
    private DisplayHistory $stepDisplayHistory;

    private DisplayResult $stepDisplayResult;

    private ContainerInterface $container;

    public function __construct(
        DisplayHistory $stepDisplayHistory,
        DisplayResult $stepDisplayResult,
        ContainerInterface $container
    ) {
        $this->stepDisplayHistory = $stepDisplayHistory;
        $this->stepDisplayResult = $stepDisplayResult;
        $this->container = $container;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => [
                ['updateContainer']
            ]
        ];
    }

    public function updateContainer(): void
    {
        $this->container->set(DispatchHistoryInterface::class, $this->stepDisplayHistory);
        $this->container->set(DispatchResultInterface::class, $this->stepDisplayResult);
    }
}
