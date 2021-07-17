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
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Laminas\Response;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Infrastructures\Laminas\Response\Error;
use Teknoo\East\Paas\Infrastructures\Laminas\Response\ErrorFactory;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Laminas\Response\ErrorFactory
 */
class ErrorFactoryTest extends TestCase
{
    public function testBuildFailurePromiseWithReason()
    {
        $factory = new ErrorFactory();
        $client = $this->createMock(ClientInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $callable = $factory->buildFailurePromise($client, $manager, 500, 'foo');
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

    public function testBuildFailurePromiseWithInvalidCode()
    {
        $factory = new ErrorFactory();
        $client = $this->createMock(ClientInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $callable = $factory->buildFailurePromise($client, $manager, 500, null);
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

    public function testBuildFailurePromiseWithNoReason()
    {
        $factory = new ErrorFactory();
        $client = $this->createMock(ClientInterface::class);
        $manager = $this->createMock(ManagerInterface::class);

        $callable = $factory->buildFailurePromise($client, $manager, 500, null);
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
