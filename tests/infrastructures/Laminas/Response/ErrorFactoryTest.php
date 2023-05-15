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

namespace Teknoo\Tests\East\Paas\Infrastructures\Laminas\Response;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Infrastructures\Laminas\Response\Error;
use Teknoo\East\Paas\Infrastructures\Laminas\Response\ErrorFactory;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Infrastructures\Laminas\Response\ErrorFactory
 */
class ErrorFactoryTest extends TestCase
{
    public function testBuildFailureHandlerWithReason()
    {
        $factory = new ErrorFactory();
        $client = $this->createMock(ClientInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $callable = $factory->buildFailureHandler($client, $manager, 500, 'foo');
        self::assertIsCallable($callable);

        $client->expects(self::once())
            ->method('acceptResponse')
            ->willReturnCallback(
                function ($error) use ($client) {
                    self::assertInstanceOf(Error::class, $error);
                    self::assertEquals('foo', $error->getReasonPhrase());
                    return $client;
                }
            );
        $manager->expects(self::once())->method('finish');

        $callable(new \RuntimeException('bar', 501));
    }

    public function testBuildFailureHandlerWithInvalidCode()
    {
        $factory = new ErrorFactory();
        $client = $this->createMock(ClientInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $callable = $factory->buildFailureHandler($client, $manager, 500, null);
        self::assertIsCallable($callable);

        $client->expects(self::once())
            ->method('acceptResponse')
            ->willReturnCallback(
                function ($error) use ($client) {
                    self::assertInstanceOf(Error::class, $error);
                    self::assertEquals('bar', $error->getReasonPhrase());
                    return $client;
                }
            );
        $manager->expects(self::once())->method('finish');

        $callable(new \RuntimeException('bar', 100));
    }

    public function testBuildFailureHandlerWithNoReason()
    {
        $factory = new ErrorFactory();
        $client = $this->createMock(ClientInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $callable = $factory->buildFailureHandler($client, $manager, 500, null);
        self::assertIsCallable($callable);

        $client->expects(self::once())
            ->method('acceptResponse')
            ->willReturnCallback(
                function ($error) use ($client) {
                    self::assertInstanceOf(Error::class, $error);
                    self::assertEquals('bar', $error->getReasonPhrase());
                    return $client;
                }
            );
        $manager->expects(self::once())->method('finish');

        $callable(new \RuntimeException('bar', 501));
    }
}
