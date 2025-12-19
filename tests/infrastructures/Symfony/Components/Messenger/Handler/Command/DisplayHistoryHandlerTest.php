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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Paas\Contracts\Security\EncryptionInterface;
use Teknoo\East\Paas\Contracts\Security\SensitiveContentInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command\DisplayHistoryHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\HistorySent;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(DisplayHistoryHandler::class)]
class DisplayHistoryHandlerTest extends TestCase
{
    public function buildStep(?EncryptionInterface $encryption): DisplayHistoryHandler
    {
        return new DisplayHistoryHandler(
            $encryption
        );
    }

    public function testInvokeWithoutOutput(): void
    {
        $this->assertInstanceOf(DisplayHistoryHandler::class, ($this->buildStep(null))($this->createStub(HistorySent::class)));
    }

    public function testInvoke(): void
    {
        $output = $this->createMock(OutputInterface::class);

        $output->expects($this->once())
            ->method('writeln');

        $this->assertInstanceOf(DisplayHistoryHandler::class, ($this->buildStep(null)->setOutput($output))($this->createStub(HistorySent::class)));
    }

    public function testInvokeWithEncryption(): void
    {
        $output = $this->createMock(OutputInterface::class);

        $output->expects($this->once())
            ->method('writeln');

        $encryption = $this->createStub(EncryptionInterface::class);
        $encryption
            ->method('decrypt')
            ->willReturnCallback(
                function (SensitiveContentInterface $data, PromiseInterface $promise) use ($encryption): MockObject|Stub {
                    $promise->success($data);

                    return $encryption;
                }
            );

        $this->assertInstanceOf(DisplayHistoryHandler::class, ($this->buildStep($encryption)->setOutput($output))($this->createStub(HistorySent::class)));
    }
}
