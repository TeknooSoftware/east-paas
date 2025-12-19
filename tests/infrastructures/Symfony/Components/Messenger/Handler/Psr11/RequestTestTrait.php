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
use PHPUnit\Framework\MockObject\Stub;
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
    private (UriFactoryInterface&MockObject)|(UriFactoryInterface&Stub)|null $uriFactory = null;

    private (RequestFactoryInterface&MockObject)|(RequestFactoryInterface&Stub)|null $requestFactory = null;

    private (StreamFactoryInterface&MockObject)|(StreamFactoryInterface&Stub)|null $streamFactory = null;

    private (ClientInterface&MockObject)|(ClientInterface&Stub)|null $client = null;

    public function getUriFactoryInterfaceMock(bool $stub = false): (UriFactoryInterface&Stub)|(UriFactoryInterface&MockObject)
    {
        if (!$this->uriFactory instanceof UriFactoryInterface) {
            if ($stub) {
                $this->uriFactory = $this->createStub(UriFactoryInterface::class);
            } else {
                $this->uriFactory = $this->createMock(UriFactoryInterface::class);
            }
        }

        return $this->uriFactory;
    }

    public function getRequestFactoryInterfaceMock(bool $stub = false): (RequestFactoryInterface&Stub)|(RequestFactoryInterface&MockObject)
    {
        if (!$this->requestFactory instanceof RequestFactoryInterface) {
            if ($stub) {
                $this->requestFactory = $this->createStub(RequestFactoryInterface::class);
            } else {
                $this->requestFactory = $this->createMock(RequestFactoryInterface::class);
            }
        }

        return $this->requestFactory;
    }

    public function getStreamFactoryInterfaceMock(bool $stub = false): (StreamFactoryInterface&Stub)|(StreamFactoryInterface&MockObject)
    {
        if (!$this->streamFactory instanceof StreamFactoryInterface) {
            if ($stub) {
                $this->streamFactory = $this->createStub(StreamFactoryInterface::class);
            } else {
                $this->streamFactory = $this->createMock(StreamFactoryInterface::class);
            }
        }

        return $this->streamFactory;
    }

    public function getClientInterfaceMock(bool $stub = false): (ClientInterface&Stub)|(ClientInterface&MockObject)
    {
        if (!$this->client instanceof ClientInterface) {
            if ($stub) {
                $this->client = $this->createStub(ClientInterface::class);
            } else {
                $this->client = $this->createMock(ClientInterface::class);
            }
        }

        return $this->client;
    }

    private function doTest($object, $class, $argument): void
    {
        $this->getUriFactoryInterfaceMock(true)
            ->method('createUri')
            ->willReturn($this->createStub(UriInterface::class));

        $request = $this->createStub(RequestInterface::class);

        $request->method('withAddedHeader')
            ->willReturnSelf();

        $request->method('withBody')
            ->willReturnSelf($request);

        $this->getRequestFactoryInterfaceMock(true)
            ->method('createRequest')
            ->willReturn($request);

        $this->getStreamFactoryInterfaceMock(true)
            ->method('createStream')
            ->willReturn($this->createStub(StreamInterface::class));

        $this->getClientInterfaceMock()
            ->expects($this->once())
            ->method('sendRequest');

        $this->assertInstanceOf(
            $class,
            $object($argument)
        );
    }
}
