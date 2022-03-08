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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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
            ->expects(self::any())
            ->method('createUri')
            ->willReturn($this->createMock(UriInterface::class));

        $request = $this->createMock(RequestInterface::class);

        $request->expects(self::any())
            ->method('withAddedHeader')
            ->willReturnSelf();

        $request->expects(self::any())
            ->method('withBody')
            ->willReturnSelf($request);

        $this->getRequestFactoryInterfaceMock()
            ->expects(self::any())
            ->method('createRequest')
            ->willReturn($request);

        $this->getStreamFactoryInterfaceMock()
            ->expects(self::any())
            ->method('createStream')
            ->willReturn($this->createMock(StreamInterface::class));

        $this->getClientInterfaceMock()
            ->expects(self::once())
            ->method('sendRequest');

        self::assertInstanceOf(
            $class,
            $object($argument)
        );
    }
}