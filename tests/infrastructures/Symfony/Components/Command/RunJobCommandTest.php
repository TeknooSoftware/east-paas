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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Foundation\Command\Executor;
use Teknoo\East\Foundation\Http\Message\MessageFactoryInterface;
use Teknoo\East\FoundationBundle\Command\Client;
use Teknoo\East\Paas\Contracts\Recipe\Plan\RunJobInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Command\RunJobCommand;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command\DisplayHistoryHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command\DisplayResultHandler;

use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(RunJobCommand::class)]
class RunJobCommandTest extends TestCase
{
    private (Executor&MockObject)|null $Executor = null;

    private (Client&MockObject)|null $client = null;

    private (RunJobInterface&MockObject)|null $runJob = null;

    private (MessageFactoryInterface&MockObject)|null $messageFactory = null;

    private (StreamFactoryInterface&MockObject)|null $streamFactory = null;

    private (DisplayHistoryHandler&MockObject)|null $stepDisplayHistory = null;

    private (DisplayResultHandler&MockObject)|null $stepDisplayResult = null;

    private function getExecutorMock(): Executor&MockObject
    {
        if (!$this->Executor instanceof Executor) {
            $this->Executor = $this->createMock(Executor::class);
        }

        return $this->Executor;
    }

    private function getClientMock(): Client&MockObject
    {
        if (!$this->client instanceof Client) {
            $this->client = $this->createMock(Client::class);
        }

        return $this->client;
    }

    private function getRunJobMock(): RunJobInterface&MockObject
    {
        if (!$this->runJob instanceof RunJobInterface) {
            $this->runJob = $this->createMock(RunJobInterface::class);
        }

        return $this->runJob;
    }

    private function getMessageFactoryMock(): MessageFactoryInterface&MockObject
    {
        if (!$this->messageFactory instanceof MessageFactoryInterface) {
            $this->messageFactory = $this->createMock(MessageFactoryInterface::class);
        }

        return $this->messageFactory;
    }

    private function getStreamFactoryMock(): StreamFactoryInterface&MockObject
    {
        if (!$this->streamFactory instanceof StreamFactoryInterface) {
            $this->streamFactory = $this->createMock(StreamFactoryInterface::class);
        }

        return $this->streamFactory;
    }

    public function getStepDisplayHistory(): DisplayHistoryHandler&MockObject
    {
        if (!$this->stepDisplayHistory instanceof DisplayHistoryHandler) {
            $this->stepDisplayHistory = $this->createMock(DisplayHistoryHandler::class);
        }

        return $this->stepDisplayHistory;
    }

    public function getStepDisplayResult(): DisplayResultHandler&MockObject
    {
        if (!$this->stepDisplayResult instanceof DisplayResultHandler) {
            $this->stepDisplayResult = $this->createMock(DisplayResultHandler::class);
        }

        return $this->stepDisplayResult;
    }

    public function buildCommand(): RunJobCommand
    {
        return new RunJobCommand(
            'teknoo:paas:run-job',
            'Run a job',
            $this->getExecutorMock(),
            $this->getClientMock(),
            $this->getRunJobMock(),
            $this->getMessageFactoryMock(),
            $this->getStreamFactoryMock(),
            $this->getStepDisplayHistory(),
            $this->getStepDisplayResult()
        );
    }

    public function testExecutionFromFile(): void
    {
        $fileName = tempnam(sys_get_temp_dir(), 'paas_test');
        file_put_contents($fileName, 'fooBar');

        $input = $this->createMock(InputInterface::class);
        $input
            ->method('getArgument')
            ->willReturn($fileName);

        $request = $this->createMock(MessageInterface::class);
        $request
            ->method('withBody')
            ->willReturnSelf();

        $this->getStreamFactoryMock()
            ->method('createStream')
            ->with('fooBar')
            ->willReturn($this->createMock(StreamInterface::class));

        $this->getMessageFactoryMock()
            ->method('createMessage')
            ->willReturn($request);

        $output = $this->createMock(OutputInterface::class);

        $this->assertEquals(0, $this->buildCommand()->run(
            $input,
            $output
        ));

        @unlink($fileName);
    }

    public function testExecutionFromInput(): void
    {
        $input = $this->createMock(InputInterface::class);
        $input
            ->method('getArgument')
            ->willReturn('fooBar');

        $request = $this->createMock(MessageInterface::class);
        $request
            ->method('withBody')
            ->willReturnSelf();

        $this->getStreamFactoryMock()
            ->method('createStream')
            ->with('fooBar')
            ->willReturn($this->createMock(StreamInterface::class));

        $this->getMessageFactoryMock()
            ->method('createMessage')
            ->willReturn($request);

        $output = $this->createMock(OutputInterface::class);

        $this->assertEquals(0, $this->buildCommand()->run(
            $input,
            $output
        ));
    }

    public function testExecutionFromInputNotAString(): void
    {
        $input = $this->createMock(InputInterface::class);
        $input
            ->method('getArgument')
            ->willReturn(123);

        $output = $this->createMock(OutputInterface::class);

        $this->assertEquals(1, $this->buildCommand()->run(
            $input,
            $output
        ));
    }
}
