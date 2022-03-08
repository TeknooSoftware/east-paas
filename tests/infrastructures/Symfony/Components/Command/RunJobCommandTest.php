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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Command;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Foundation\Http\Message\MessageFactoryInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\FoundationBundle\Command\Client;
use Teknoo\East\Paas\Contracts\Recipe\Cookbook\RunJobInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Command\RunJobCommand;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command\DisplayHistoryHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command\DisplayResultHandler;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Symfony\Command\RunJobCommand
 */
class RunJobCommandTest extends TestCase
{
    private ?ManagerInterface $manager = null;

    private ?Client $client = null;

    private ?RunJobInterface $runJob = null;

    private ?MessageFactoryInterface $messageFactory = null;

    private ?StreamFactoryInterface $streamFactory = null;

    private ?DisplayHistoryHandler $stepDisplayHistory = null;

    private ?DisplayResultHandler $stepDisplayResult = null;

    /**
     * @return ManagerInterface|MockObject
     */
    private function getManagerMock(): ManagerInterface
    {
        if (!$this->manager instanceof ManagerInterface) {
            $this->manager = $this->createMock(ManagerInterface::class);
        }

        return $this->manager;
    }
    
    /**
     * @return Client|MockObject
     */
    private function getClientMock(): Client
    {
        if (!$this->client instanceof Client) {
            $this->client = $this->createMock(Client::class);
        }

        return $this->client;
    }

    /**
     * @return RunJobInterface|MockObject
     */
    private function getRunJobMock(): RunJobInterface
    {
        if (!$this->runJob instanceof RunJobInterface) {
            $this->runJob = $this->createMock(RunJobInterface::class);
        }

        return $this->runJob;
    }

    /**
     * @return MessageFactoryInterface|MockObject
     */
    private function getMessageFactoryMock(): MessageFactoryInterface
    {
        if (!$this->messageFactory instanceof MessageFactoryInterface) {
            $this->messageFactory = $this->createMock(MessageFactoryInterface::class);
        }

        return $this->messageFactory;
    }

    /**
     * @return StreamFactoryInterface|MockObject
     */
    private function getStreamFactoryMock(): StreamFactoryInterface
    {
        if (!$this->streamFactory instanceof StreamFactoryInterface) {
            $this->streamFactory = $this->createMock(StreamFactoryInterface::class);
        }

        return $this->streamFactory;
    }

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

    public function buildCommand(): RunJobCommand
    {
        return new RunJobCommand(
            'teknoo:paas:run-job',
            'Run a job',
            $this->getManagerMock(),
            $this->getClientMock(),
            $this->getRunJobMock(),
            $this->getMessageFactoryMock(),
            $this->getStreamFactoryMock(),
            $this->getStepDisplayHistory(),
            $this->getStepDisplayResult()
        );
    }

    public function testExecutionFromFile()
    {
        $fileName = \tempnam(\sys_get_temp_dir(), 'paas_test');
        \file_put_contents($fileName, 'fooBar');

        $input = $this->createMock(InputInterface::class);
        $input->expects(self::any())
            ->method('getArgument')
            ->willReturn($fileName);

        $request = $this->createMock(MessageInterface::class);
        $request->expects(self::any())
            ->method('withBody')
            ->willReturnSelf();

        $this->getStreamFactoryMock()
            ->expects(self::any())
            ->method('createStream')
            ->with('fooBar')
            ->willReturn($this->createMock(StreamInterface::class));

        $this->getMessageFactoryMock()
            ->expects(self::any())
            ->method('createMessage')
            ->willReturn($request);

        $output = $this->createMock(OutputInterface::class);

        self::assertEquals(
            0,
            $this->buildCommand()->run(
                $input,
                $output
            )
        );

        @\unlink($fileName);
    }

    public function testExecutionFromInput()
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects(self::any())
            ->method('getArgument')
            ->willReturn('fooBar');

        $request = $this->createMock(MessageInterface::class);
        $request->expects(self::any())
            ->method('withBody')
            ->willReturnSelf();

        $this->getStreamFactoryMock()
            ->expects(self::any())
            ->method('createStream')
            ->with('fooBar')
            ->willReturn($this->createMock(StreamInterface::class));

        $this->getMessageFactoryMock()
            ->expects(self::any())
            ->method('createMessage')
            ->willReturn($request);

        $output = $this->createMock(OutputInterface::class);

        self::assertEquals(
            0,
            $this->buildCommand()->run(
                $input,
                $output
            )
        );
    }

    public function testExecutionFromInputNotAString()
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects(self::any())
            ->method('getArgument')
            ->willReturn(123);

        $output = $this->createMock(OutputInterface::class);

        self::assertEquals(
            1,
            $this->buildCommand()->run(
                $input,
                $output
            )
        );
    }
}
