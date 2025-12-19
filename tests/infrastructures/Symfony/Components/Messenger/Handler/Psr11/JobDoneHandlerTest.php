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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Messenger\Handler\Psr11;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Contracts\Security\EncryptionInterface;
use Teknoo\East\Paas\Contracts\Security\SensitiveContentInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Psr11\JobDoneHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\JobDone;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(JobDoneHandler::class)]
class JobDoneHandlerTest extends TestCase
{
    use RequestTestTrait;

    public function testInvoke(): void
    {
        $this->doTest(
            (new JobDoneHandler(
                'foo',
                'bar',
                null,
                $this->getUriFactoryInterfaceMock(true),
                $this->getRequestFactoryInterfaceMock(true),
                $this->getStreamFactoryInterfaceMock(true),
                $this->getClientInterfaceMock()
            )),
            JobDoneHandler::class,
            new JobDone('foo', 'bar', 'foo', 'bar')
        );
    }

    public function testInvokeWithEncryption(): void
    {
        $encryption = $this->createStub(EncryptionInterface::class);
        $encryption
            ->method('decrypt')
            ->willReturnCallback(
                function (SensitiveContentInterface $data, PromiseInterface $promise) use ($encryption): MockObject|Stub {
                    $promise->success($data);

                    return $encryption;
                }
            );

        $this->doTest(
            (new JobDoneHandler(
                'foo',
                'bar',
                $encryption,
                $this->getUriFactoryInterfaceMock(true),
                $this->getRequestFactoryInterfaceMock(true),
                $this->getStreamFactoryInterfaceMock(true),
                $this->getClientInterfaceMock()
            )),
            JobDoneHandler::class,
            new JobDone('foo', 'bar', 'foo', 'bar')
        );
    }
}
