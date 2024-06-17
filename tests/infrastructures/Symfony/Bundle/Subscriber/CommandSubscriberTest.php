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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\EastPaasBundle\Subscriber;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\EastPaasBundle\Subscriber\CommandSubscriber;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command\DisplayHistoryHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command\DisplayResultHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Forward\HistorySentHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Forward\JobDoneHandler;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(CommandSubscriber::class)]
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
