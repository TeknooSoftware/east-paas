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

namespace Teknoo\Tests\East\Paas\Recipe\Step\History;

use Laminas\Diactoros\Stream;
use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use Teknoo\East\Paas\Recipe\Step\History\ReceiveHistory;
use Teknoo\Recipe\ChefInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
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
            $this->createMock(MessageInterface::class),
            new \stdClass()
        );
    }

    public function testInvoke()
    {
        $message = $this->createMock(MessageInterface::class);
        $chef = $this->createMock(ChefInterface::class);

        $message->expects(self::once())
            ->method('getBody')
            ->willReturn((new StreamFactory())->createStream('foo'));

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
