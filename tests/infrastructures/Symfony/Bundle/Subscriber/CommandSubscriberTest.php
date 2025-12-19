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

namespace Teknoo\Tests\East\Paas\Infrastructures\EastPaasBundle\Subscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\EastPaasBundle\Subscriber\CommandSubscriber;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command\DisplayHistoryHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command\DisplayResultHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Forward\HistorySentHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Forward\JobDoneHandler;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(CommandSubscriber::class)]
class CommandSubscriberTest extends TestCase
{
    private (DisplayHistoryHandler&MockObject)|(DisplayHistoryHandler&Stub)|null $stepDisplayHistory = null;

    private (DisplayResultHandler&MockObject)|(DisplayResultHandler&Stub)|null $stepDisplayResult = null;

    private (HistorySentHandler&MockObject)|(HistorySentHandler&Stub)|null $historyForwarder = null;

    private (JobDoneHandler&MockObject)|(JobDoneHandler&Stub)|null $jobForwarder = null;

    public function getStepDisplayHistory(bool $stub = false): (DisplayHistoryHandler&Stub)|(DisplayHistoryHandler&MockObject)
    {
        if (!$this->stepDisplayHistory instanceof DisplayHistoryHandler) {
            if ($stub) {
                $this->stepDisplayHistory = $this->createStub(DisplayHistoryHandler::class);
            } else {
                $this->stepDisplayHistory = $this->createMock(DisplayHistoryHandler::class);
            }
        }

        return $this->stepDisplayHistory;
    }

    public function getStepDisplayResult(bool $stub = false): (DisplayResultHandler&Stub)|(DisplayResultHandler&MockObject)
    {
        if (!$this->stepDisplayResult instanceof DisplayResultHandler) {
            if ($stub) {
                $this->stepDisplayResult = $this->createStub(DisplayResultHandler::class);
            } else {
                $this->stepDisplayResult = $this->createMock(DisplayResultHandler::class);
            }
        }

        return $this->stepDisplayResult;
    }

    public function getHistoryForwarder(bool $stub = false): (HistorySentHandler&Stub)|(HistorySentHandler&MockObject)
    {
        if (!$this->historyForwarder instanceof HistorySentHandler) {
            if ($stub) {
                $this->historyForwarder = $this->createStub(HistorySentHandler::class);
            } else {
                $this->historyForwarder = $this->createMock(HistorySentHandler::class);
            }
        }

        return $this->historyForwarder;
    }

    public function getJobForwarder(bool $stub = false): (JobDoneHandler&Stub)|(JobDoneHandler&MockObject)
    {
        if (!$this->jobForwarder instanceof JobDoneHandler) {
            if ($stub) {
                $this->jobForwarder = $this->createStub(JobDoneHandler::class);
            } else {
                $this->jobForwarder = $this->createMock(JobDoneHandler::class);
            }
        }

        return $this->jobForwarder;
    }

    private function buildConfiguration(): CommandSubscriber
    {
        return new CommandSubscriber(
            $this->getStepDisplayHistory(true),
            $this->getStepDisplayResult(true),
            $this->getHistoryForwarder(true),
            $this->getJobForwarder(true)
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertIsArray(CommandSubscriber::getSubscribedEvents());
    }

    public function testUpdateForwarders(): void
    {
        $this->assertInstanceOf(CommandSubscriber::class, $this->buildConfiguration()->updateForwarders());
    }
}
