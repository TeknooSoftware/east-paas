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

namespace Teknoo\Tests\East\Paas\Infrastructures\Laminas\Response;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Infrastructures\Laminas\Response\Error;
use Teknoo\East\Paas\Infrastructures\Laminas\Response\ErrorFactory;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ErrorFactory::class)]
class ErrorFactoryTest extends TestCase
{
    public function testBuildFailureHandlerWithReason(): void
    {
        $factory = new ErrorFactory();
        $client = $this->createMock(ClientInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $callable = $factory->buildFailureHandler($client, $manager, 500, 'foo');
        $this->assertIsCallable($callable);

        $client->expects($this->once())
            ->method('acceptResponse')
            ->willReturnCallback(
                function ($error) use ($client): MockObject {
                    $this->assertInstanceOf(Error::class, $error);
                    $this->assertEquals('foo', $error->getReasonPhrase());
                    return $client;
                }
            );
        $manager->expects($this->once())->method('finish');

        $callable(new RuntimeException('bar', 501));
    }

    public function testBuildFailureHandlerWithInvalidCode(): void
    {
        $factory = new ErrorFactory();
        $client = $this->createMock(ClientInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $callable = $factory->buildFailureHandler($client, $manager, 500, null);
        $this->assertIsCallable($callable);

        $client->expects($this->once())
            ->method('acceptResponse')
            ->willReturnCallback(
                function ($error) use ($client): MockObject {
                    $this->assertInstanceOf(Error::class, $error);
                    $this->assertEquals('bar', $error->getReasonPhrase());
                    return $client;
                }
            );
        $manager->expects($this->once())->method('finish');

        $callable(new RuntimeException('bar', 100));
    }

    public function testBuildFailureHandlerWithNoReason(): void
    {
        $factory = new ErrorFactory();
        $client = $this->createMock(ClientInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $callable = $factory->buildFailureHandler($client, $manager, 500, null);
        $this->assertIsCallable($callable);

        $client->expects($this->once())
            ->method('acceptResponse')
            ->willReturnCallback(
                function ($error) use ($client): MockObject {
                    $this->assertInstanceOf(Error::class, $error);
                    $this->assertEquals('bar', $error->getReasonPhrase());
                    return $client;
                }
            );
        $manager->expects($this->once())->method('finish');

        $callable(new RuntimeException('bar', 501));
    }
}
