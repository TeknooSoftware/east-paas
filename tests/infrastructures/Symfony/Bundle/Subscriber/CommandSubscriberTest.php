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

namespace Teknoo\Tests\East\Paas\Infrastructures\EastPaasBundle\Subscriber;


use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\EastPaasBundle\Subscriber\CommandSubscriber;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command\DisplayHistoryHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command\DisplayResultHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Forward\HistorySentHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Forward\JobDoneHandler;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers      \Teknoo\East\Paas\Infrastructures\EastPaasBundle\Subscriber\CommandSubscriber
 */
class CommandSubscriberTest extends TestCase
{
    private ?DisplayHistoryHandler $stepDisplayHistory = null;

    private ?DisplayResultHandler $stepDisplayResult = null;

    private ?HistorySentHandler $historyForwarder = null;

    private ?JobDoneHandler $jobForwarder = null;

    /**
     * @return DisplayHistoryHandler|MockObject
     */
    public function getStepDisplayHistory(): ?DisplayHistoryHandler
    {
        if (!$this->stepDisplayHistory instanceof DisplayHistoryHandler) {
            $this->stepDisplayHistory = $this->createMock(DisplayHistoryHandler::class);
        }

        return $this->stepDisplayHistory;
    }

    /**
     * @return DisplayResultHandler|MockObject
     */
    public function getStepDisplayResult(): ?DisplayResultHandler
    {
        if (!$this->stepDisplayResult instanceof DisplayResultHandler) {
            $this->stepDisplayResult = $this->createMock(DisplayResultHandler::class);
        }

        return $this->stepDisplayResult;
    }

    /**
     * @return HistorySentHandler|MockObject
     */
    public function getHistoryForwarder(): ?HistorySentHandler
    {
        if (!$this->historyForwarder instanceof HistorySentHandler) {
            $this->historyForwarder = $this->createMock(HistorySentHandler::class);
        }

        return $this->historyForwarder;
    }

    /**
     * @return JobDoneHandler|MockObject
     */
    public function getJobForwarder(): ?JobDoneHandler
    {
        if (!$this->jobForwarder instanceof JobDoneHandler) {
            $this->jobForwarder = $this->createMock(JobDoneHandler::class);
        }

        return $this->jobForwarder;
    }

    /**
     * @return CommandSubscriber
     */
    private function buildConfiguration(): CommandSubscriber
    {
        return new CommandSubscriber(
            $this->getStepDisplayHistory(),
            $this->getStepDisplayResult(),
            $this->getHistoryForwarder(),
            $this->getJobForwarder()
        );
    }

    public function testGetSubscribedEvents()
    {
        self::assertIsArray(
            CommandSubscriber::getSubscribedEvents()
        );
    }

    public function testUpdateForwarders()
    {
        self::assertInstanceOf(
            CommandSubscriber::class,
            $this->buildConfiguration()->updateForwarders()
        );
    }
}
