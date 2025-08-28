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

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
trait RequestTestTrait
{
    private (UriFactoryInterface&MockObject)|null $uriFactory = null;

    private (RequestFactoryInterface&MockObject)|null $requestFactory = null;

    private (StreamFactoryInterface&MockObject)|null $streamFactory = null;

    private (ClientInterface&MockObject)|null $client = null;

    public function getUriFactoryInterfaceMock(): UriFactoryInterface&MockObject
    {
        if (!$this->uriFactory instanceof UriFactoryInterface) {
            $this->uriFactory = $this->createMock(UriFactoryInterface::class);
        }

        return $this->uriFactory;
    }

    public function getRequestFactoryInterfaceMock(): RequestFactoryInterface&MockObject
    {
        if (!$this->requestFactory instanceof RequestFactoryInterface) {
            $this->requestFactory = $this->createMock(RequestFactoryInterface::class);
        }

        return $this->requestFactory;
    }

    public function getStreamFactoryInterfaceMock(): StreamFactoryInterface&MockObject
    {
        if (!$this->streamFactory instanceof StreamFactoryInterface) {
            $this->streamFactory = $this->createMock(StreamFactoryInterface::class);
        }

        return $this->streamFactory;
    }

    public function getClientInterfaceMock(): ClientInterface&MockObject
    {
        if (!$this->client instanceof ClientInterface) {
            $this->client = $this->createMock(ClientInterface::class);
        }

        return $this->client;
    }

    private function doTest($object, $class, $argument): void
    {
        $this->getUriFactoryInterfaceMock()
            ->expects($this->any())
            ->method('createUri')
            ->willReturn($this->createMock(UriInterface::class));

        $request = $this->createMock(RequestInterface::class);

        $request->expects($this->any())
            ->method('withAddedHeader')
            ->willReturnSelf();

        $request->expects($this->any())
            ->method('withBody')
            ->willReturnSelf($request);

        $this->getRequestFactoryInterfaceMock()
            ->expects($this->any())
            ->method('createRequest')
            ->willReturn($request);

        $this->getStreamFactoryInterfaceMock()
            ->expects($this->any())
            ->method('createStream')
            ->willReturn($this->createMock(StreamInterface::class));

        $this->getClientInterfaceMock()
            ->expects($this->once())
            ->method('sendRequest');

        $this->assertInstanceOf(
            $class,
            $object($argument)
        );
    }
}
