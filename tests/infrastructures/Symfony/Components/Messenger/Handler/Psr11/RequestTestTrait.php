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

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
trait RequestTestTrait
{
    private ?UriFactoryInterface $uriFactory = null;

    private ?RequestFactoryInterface $requestFactory = null;

    private ?StreamFactoryInterface $streamFactory = null;

    private ?ClientInterface $client = null;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|UriFactoryInterface
     */
    public function getUriFactoryInterfaceMock(): UriFactoryInterface
    {
        if (!$this->uriFactory instanceof UriFactoryInterface) {
            $this->uriFactory = $this->createMock(UriFactoryInterface::class);
        }

        return $this->uriFactory;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|RequestFactoryInterface
     */
    public function getRequestFactoryInterfaceMock(): RequestFactoryInterface
    {
        if (!$this->requestFactory instanceof RequestFactoryInterface) {
            $this->requestFactory = $this->createMock(RequestFactoryInterface::class);
        }

        return $this->requestFactory;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|StreamFactoryInterface
     */
    public function getStreamFactoryInterfaceMock(): StreamFactoryInterface
    {
        if (!$this->streamFactory instanceof StreamFactoryInterface) {
            $this->streamFactory = $this->createMock(StreamFactoryInterface::class);
        }

        return $this->streamFactory;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ClientInterface
     */
    public function getClientInterfaceMock(): ClientInterface
    {
        if (!$this->client instanceof ClientInterface) {
            $this->client = $this->createMock(ClientInterface::class);
        }

        return $this->client;
    }

    private function doTest($object, $class, $argument)
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

        self::assertInstanceOf(
            $class,
            $object($argument)
        );
    }
}