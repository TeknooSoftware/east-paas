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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\Job;

use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Recipe\Step\Job\ReceiveJob;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ReceiveJob::class)]
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
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadManager()
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
        $manager = $this->createMock(ManagerInterface::class);

        $message->expects($this->once())
            ->method('getBody')
            ->willReturn((new StreamFactory())->createStream('foo'));

        $manager->expects($this->once())
            ->method('updateWorkPlan')
            ->with(['serializedJob' => 'foo'])
            ->willReturnSelf();

        self::assertInstanceOf(
            ReceiveJob::class,
            ($this->buildStep())($message, $manager)
        );
    }
}
