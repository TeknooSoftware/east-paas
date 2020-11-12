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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Command;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\FoundationBundle\Command\Client;
use Teknoo\East\Paas\Contracts\Recipe\Cookbook\RunJobInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Command\RunJobCommand;

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

    private ?ServerRequestFactoryInterface $serverRequestFactory = null;

    private ?StreamFactoryInterface $streamFactory = null;

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
     * @return ServerRequestFactoryInterface|MockObject
     */
    private function getServerRequestFactoryMock(): ServerRequestFactoryInterface
    {
        if (!$this->serverRequestFactory instanceof ServerRequestFactoryInterface) {
            $this->serverRequestFactory = $this->createMock(ServerRequestFactoryInterface::class);
        }

        return $this->serverRequestFactory;
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

    public function buildCommand(): RunJobCommand
    {
        return new RunJobCommand(
            'teknoo:paas:run-job',
            'Run a job',
            $this->getManagerMock(),
            $this->getClientMock(),
            $this->getRunJobMock(),
            $this->getServerRequestFactoryMock(),
            $this->getStreamFactoryMock()
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

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::any())
            ->method('withBody')
            ->willReturnSelf();

        $this->getStreamFactoryMock()
            ->expects(self::any())
            ->method('createStream')
            ->with('fooBar')
            ->willReturn($this->createMock(StreamInterface::class));

        $this->getServerRequestFactoryMock()
            ->expects(self::any())
            ->method('createServerRequest')
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

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::any())
            ->method('withBody')
            ->willReturnSelf();

        $this->getStreamFactoryMock()
            ->expects(self::any())
            ->method('createStream')
            ->with('fooBar')
            ->willReturn($this->createMock(StreamInterface::class));

        $this->getServerRequestFactoryMock()
            ->expects(self::any())
            ->method('createServerRequest')
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
