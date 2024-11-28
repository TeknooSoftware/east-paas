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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Messenger\Handler\Psr11;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Contracts\Security\EncryptionInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Psr11\JobDoneHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\JobDone;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(JobDoneHandler::class)]
class JobDoneHandlerTest extends TestCase
{
    use RequestTestTrait;

    public function testInvoke()
    {
        $this->doTest(
            (new JobDoneHandler(
                'foo',
                'bar',
                null,
                $this->getUriFactoryInterfaceMock(),
                $this->getRequestFactoryInterfaceMock(),
                $this->getStreamFactoryInterfaceMock(),
                $this->getClientInterfaceMock()
            )),
            JobDoneHandler::class,
            new JobDone('foo', 'bar', 'foo', 'bar')
        );
    }

    public function testInvokeWithEncryption()
    {
        $encryption = $this->createMock(EncryptionInterface::class);
        $encryption->expects($this->any())
            ->method('decrypt')
            ->willReturnCallback(
                function ($data, PromiseInterface $promise) use ($encryption) {
                    $promise->success($data);

                    return $encryption;
                }
            );

        $this->doTest(
            (new JobDoneHandler(
                'foo',
                'bar',
                $encryption,
                $this->getUriFactoryInterfaceMock(),
                $this->getRequestFactoryInterfaceMock(),
                $this->getStreamFactoryInterfaceMock(),
                $this->getClientInterfaceMock()
            )),
            JobDoneHandler::class,
            new JobDone('foo', 'bar', 'foo', 'bar')
        );
    }
}