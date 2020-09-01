<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Messenger\Transport;

use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Job;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Parameter;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Transport\GuzzleTransport;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;

class GuzzleTransportTest extends TestCase
{
    /**
     * @var Client
     */
    private $guzzle;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Client
     */
    public function getGuzzleMock(): Client
    {
        if (!$this->guzzle instanceof Client) {
            $this->guzzle = $this->createMock(Client::class);
        }

        return $this->guzzle;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    public function getLoggerMock(): LoggerInterface
    {
        if (!$this->logger instanceof LoggerInterface) {
            $this->logger = $this->createMock(LoggerInterface::class);
        }

        return $this->logger;
    }

    /**
     * @return GuzzleTransport
     */
    public function buildTransport(): GuzzleTransport
    {
        return new GuzzleTransport($this->getGuzzleMock(), $this->getLoggerMock());
    }

    public function testConfigure()
    {
        self::assertInstanceOf(
            GuzzleTransport::class,
            $this->buildTransport()->configure('foo', 'bar')
        );
    }

    public function testSendSuccess()
    {
        $envelope = new Envelope(
            new Job('{foo:bar}'), [
            new Parameter('id', '123'),
            new Parameter('env', 'hello')
        ]);

        $promise = new Promise();

        $this->getLoggerMock()
            ->expects(self::never())
            ->method('error');

        $this->getGuzzleMock()->expects(self::once())
            ->method('requestAsync')
            ->with('put', 'https://foo.com/123/bar/hello', ['body' => '{foo:bar}'])
            ->willReturn($promise);

        $transport = $this->buildTransport();
        $transport = clone $transport;
        $transport->configure('put', 'https://foo.com/%id/bar/%env');
        self::assertInstanceOf(Envelope::class, $promise = $transport->send($envelope));
    }

    public function testSendError()
    {
        $envelope = new Envelope(
            new Job('{foo:bar}'), [
            new Parameter('id', '123'),
            new Parameter('env', 'hello')
        ]);

        $promise = $this->createMock(Promise::class);
        $promise->expects(self::any())
            ->method('then')
            ->willReturnCallback(function ($success, $error) {
                $error(new RequestException('foo-bar', $this->createMock(Request::class)));
            });

        $this->getLoggerMock()
            ->expects(self::once())
            ->method('error')
            ->with('foo-bar');

        $this->getGuzzleMock()->expects(self::once())
            ->method('requestAsync')
            ->with('put', 'https://foo.com/123/bar/hello', ['body' => '{foo:bar}'])
            ->willReturn($promise);

        $transport = $this->buildTransport();
        $transport = clone $transport;
        $transport->configure('put', 'https://foo.com/%id/bar/%env');
        self::assertInstanceOf(Envelope::class, $promise = $transport->send($envelope));
    }

    public function testReceive()
    {
        $this->expectException(\LogicException::class);
        $this->buildTransport()->receive(function() {});
    }

    public function testStop()
    {
        $this->expectException(\LogicException::class);
        $this->buildTransport()->stop();
    }

    public function testGet()
    {
        $this->expectException(\LogicException::class);
        $this->buildTransport()->get();
    }

    public function testAck()
    {
        $this->expectException(\LogicException::class);
        $this->buildTransport()->ack(
            new Envelope(new \stdClass())
        );
    }

    public function testReject()
    {
        $this->expectException(\LogicException::class);
        $this->buildTransport()->reject(
            new Envelope(new \stdClass())
        );
    }
}