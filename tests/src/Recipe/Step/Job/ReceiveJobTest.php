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

namespace Teknoo\Tests\East\Paas\Recipe\Step\Job;

use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use stdClass;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Recipe\Step\Job\ReceiveJob;
use TypeError;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ReceiveJob::class)]
class ReceiveJobTest extends TestCase
{
    public function buildStep(): ReceiveJob
    {
        return new ReceiveJob();
    }

    public function testInvokeBadMessage(): void
    {
        $this->expectException(TypeError::class);
        ($this->buildStep())(
            new stdClass(),
            $this->createStub(ManagerInterface::class)
        );
    }

    public function testInvokeBadManager(): void
    {
        $this->expectException(TypeError::class);
        ($this->buildStep())(
            $this->createStub(MessageInterface::class),
            new stdClass()
        );
    }

    public function testInvoke(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(new StreamFactory()->createStream('foo'));

        $manager->expects($this->once())
            ->method('updateWorkPlan')
            ->with(['serializedJob' => 'foo'])
            ->willReturnSelf();

        $this->assertInstanceOf(ReceiveJob::class, ($this->buildStep())($message, $manager));
    }
}
