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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Messenger\Handler\Forward;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\Symfony\Contracts\Messenger\Handler\HistorySentHandlerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Forward\HistorySentHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\HistorySent;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Forward\HistorySentHandler
 */
class HistorySentHandlerTest extends TestCase
{
    public function buildStep(): HistorySentHandler
    {
        return new HistorySentHandler();
    }

    public function testInvokeWithoutHandler()
    {
        self::assertInstanceOf(
            HistorySentHandler::class,
            ($this->buildStep())($this->createMock(HistorySent::class))
        );
    }

    public function testInvoke()
    {
        $handler = $this->createMock(HistorySentHandlerInterface::class);

        $handler->expects(self::once())
            ->method('__invoke');

        self::assertInstanceOf(
            HistorySentHandler::class,
            ($this->buildStep()->setHandler($handler))($this->createMock(HistorySent::class))
        );
    }
}
