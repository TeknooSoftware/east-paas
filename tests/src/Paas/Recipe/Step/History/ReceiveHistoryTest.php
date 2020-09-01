<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\History;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Teknoo\East\Paas\Recipe\Step\History\ReceiveHistory;
use Teknoo\Recipe\ChefInterface;

/**
 * @covers \Teknoo\East\Paas\Recipe\Step\History\ReceiveHistory
 */
class ReceiveHistoryTest extends TestCase
{
    public function buildStep(): ReceiveHistory
    {
        return new ReceiveHistory();
    }

    public function testInvokeBadMessage()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(
            new \stdClass(),
            $this->createMock(ChefInterface::class)
        );
    }

    public function testInvokeBadChef()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(
            $this->createMock(ServerRequestInterface::class),
            new \stdClass()
        );
    }

    public function testInvoke()
    {
        $message = $this->createMock(ServerRequestInterface::class);
        $chef = $this->createMock(ChefInterface::class);

        $message->expects(self::once())
            ->method('getBody')
            ->willReturn('foo');

        $chef->expects(self::once())
            ->method('updateWorkPlan')
            ->with(['serializedHistory' => 'foo'])
            ->willReturnSelf();

        self::assertInstanceOf(
            ReceiveHistory::class,
            ($this->buildStep())($message, $chef)
        );
    }
}
