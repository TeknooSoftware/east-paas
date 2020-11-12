<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
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

namespace Teknoo\Tests\East\Paas\Infrastructures\Guzzle;

use GuzzleHttp\Promise\PromiseInterface;
use Teknoo\East\Paas\Infrastructures\Guzzle\AsyncResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Guzzle\AsyncResponse
 */
class AsyncResponseTest extends TestCase
{
    private ?PromiseInterface $promise = null;

    private ?ResponseInterface $response = null;

    /**
     * @return PromiseInterface|MockObject
     */
    private function getPromiseMock(): ?PromiseInterface
    {
        if (!$this->promise instanceof PromiseInterface) {
            $this->promise = $this->createMock(PromiseInterface::class);

            $this->promise->expects(self::any())
                ->method('wait')
                ->willReturn($this->getResponseMock());
        }

        return $this->promise;
    }

    /**
     * @return ResponseInterface|MockObject
     */
    private function getResponseMock(): ?ResponseInterface
    {
        if (!$this->response instanceof ResponseInterface) {
            $this->response = $this->createMock(ResponseInterface::class);
        }

        return $this->response;
    }

    public function buildResponse(): AsyncResponse
    {
        return new AsyncResponse($this->getPromiseMock());
    }

    public function testGetProtocolVersion()
    {
        $this->getResponseMock()
            ->expects(self::any())
            ->method('getProtocolVersion')
            ->willReturn('foo');

        self::assertEquals(
            'foo',
            $this->buildResponse()->getProtocolVersion()
        );
    }

    public function testWithProtocolVersion()
    {
        $this->expectException(\LogicException::class);
        $this->buildResponse()->withProtocolVersion('foo');
    }

    public function testGetHeaders()
    {
        $this->getResponseMock()
            ->expects(self::any())
            ->method('getHeaders')
            ->willReturn(['foo']);

        self::assertEquals(
            ['foo'],
            $this->buildResponse()->getHeaders()
        );
    }

    public function testHasHeader()
    {
        $this->getResponseMock()
            ->expects(self::any())
            ->method('hasHeader')
            ->with('foo')
            ->willReturn(true);

        self::assertEquals(
            true,
            $this->buildResponse()->hasHeader('foo')
        );
    }

    public function testGetHeader()
    {
        $this->getResponseMock()
            ->expects(self::any())
            ->method('getHeader')
            ->with('foo')
            ->willReturn('bar');

        self::assertEquals(
            'bar',
            $this->buildResponse()->getHeader('foo')
        );
    }

    public function testGetHeaderLine()
    {
        $this->getResponseMock()
            ->expects(self::any())
            ->method('getHeaderLine')
            ->with('foo')
            ->willReturn('bar');

        self::assertEquals(
            'bar',
            $this->buildResponse()->getHeaderLine('foo')
        );
    }

    public function testWithHeader()
    {
        $this->expectException(\LogicException::class);
        $this->buildResponse()->withHeader('foo', 'bar');
    }

    public function testWithAddedHeader()
    {
        $this->expectException(\LogicException::class);
        $this->buildResponse()->withAddedHeader('foo', 'bar');
    }

    public function testWithoutHeader()
    {
        $this->expectException(\LogicException::class);
        $this->buildResponse()->withoutHeader('foo');
    }

    public function testGetBody()
    {
        $this->getResponseMock()
            ->expects(self::any())
            ->method('getBody')
            ->willReturn($this->createMock(StreamInterface::class));

        self::assertInstanceOf(
            StreamInterface::class,
            $this->buildResponse()->getBody()
        );
    }

    public function testWithBody()
    {
        $this->expectException(\LogicException::class);
        $this->buildResponse()->withBody($this->createMock(StreamInterface::class));
    }

    public function testGetStatusCode()
    {
        $this->getResponseMock()
            ->expects(self::any())
            ->method('getStatusCode')
            ->willReturn(200);

        self::assertEquals(
            200,
            $this->buildResponse()->getStatusCode()
        );
    }

    public function testWithStatus()
    {
        $this->expectException(\LogicException::class);
        $this->buildResponse()->withStatus('foo');
    }

    public function testGetReasonPhrase()
    {
        $this->getResponseMock()
            ->expects(self::any())
            ->method('getReasonPhrase')
            ->willReturn('foo');

        $response = $this->buildResponse();
        self::assertEquals(
            'foo',
            $response->getReasonPhrase()
        );
        self::assertEquals(
            'foo',
            $response->getReasonPhrase()
        );
    }
}

