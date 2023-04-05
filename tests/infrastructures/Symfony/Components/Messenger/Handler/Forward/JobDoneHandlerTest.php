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
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Messenger\Handler\Forward;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\Symfony\Contracts\Messenger\Handler\JobDoneHandlerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Forward\JobDoneHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\JobDone;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Forward\JobDoneHandler
 */
class JobDoneHandlerTest extends TestCase
{
    public function buildStep(): JobDoneHandler
    {
        return new JobDoneHandler();
    }

    public function testInvoke()
    {
        $handler = $this->createMock(JobDoneHandlerInterface::class);

        $handler->expects(self::once())
            ->method('__invoke');

        self::assertInstanceOf(
            JobDoneHandler::class,
                ($this->buildStep()->setHandler($handler))($this->createMock(JobDone::class)
            )
        );
    }

    public function testInvokeWithoutHandler()
    {
        self::assertInstanceOf(
            JobDoneHandler::class,
            ($this->buildStep())($this->createMock(JobDone::class)
            )
        );
    }
}