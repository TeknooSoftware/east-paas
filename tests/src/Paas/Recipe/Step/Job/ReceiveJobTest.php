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
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\Job;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Teknoo\East\Paas\Recipe\Step\Job\ReceiveJob;
use Teknoo\Recipe\ChefInterface;

/**
 * @covers \Teknoo\East\Paas\Recipe\Step\Job\ReceiveJob
 */
class ReceiveJobTest extends TestCase
{
    public function buildStep(): ReceiveJob
    {
        return new ReceiveJob();
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
            ->with(['serializedJob' => 'foo'])
            ->willReturnSelf();

        self::assertInstanceOf(
            ReceiveJob::class,
            ($this->buildStep())($message, $chef)
        );
    }
}
