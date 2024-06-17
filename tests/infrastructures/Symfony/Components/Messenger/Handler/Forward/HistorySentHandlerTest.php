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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Messenger\Handler\Forward;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\Symfony\Contracts\Messenger\Handler\HistorySentHandlerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Forward\HistorySentHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\HistorySent;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(HistorySentHandler::class)]
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

        $handler->expects($this->once())
            ->method('__invoke');

        self::assertInstanceOf(
            HistorySentHandler::class,
            ($this->buildStep()->setHandler($handler))($this->createMock(HistorySent::class))
        );
    }
}
