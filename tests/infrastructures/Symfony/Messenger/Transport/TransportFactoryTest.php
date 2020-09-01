<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Messenger\Transport;

use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Transport\GuzzleTransport;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Transport\TransportFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class TransportFactoryTest extends TestCase
{
    /**
     * @var GuzzleTransport
     */
    private $guzzleTransport;

    /**
     * @var string
     */
    private $protocol = 'tkpaas://';

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|GuzzleTransport
     */
    public function getTransportMock(): GuzzleTransport
    {
        if (!$this->guzzleTransport instanceof GuzzleTransport) {
            $this->guzzleTransport = $this->createMock(GuzzleTransport::class);
        }

        return $this->guzzleTransport;
    }

    public function buildFactory(): TransportFactory
    {
        return new TransportFactory($this->getTransportMock(), $this->protocol);
    }

    public function testCreateTransportBadDsn()
    {
        $this->expectException(\RuntimeException::class);
        $this->buildFactory()->createTransport('foo://bar', [], $this->createMock(SerializerInterface::class));
    }

    public function testCreateTransport()
    {
        $this->getTransportMock()
            ->expects(self::once())
            ->method('configure')
            ->with('put', 'https://bar')
            ->willReturnSelf();

        self::assertEquals(
            $this->getTransportMock(),
            $this->buildFactory()->createTransport('tkpaas://put:bar', [], $this->createMock(SerializerInterface::class))
        );
    }

    public function testSupports()
    {
        self::assertFalse($this->buildFactory()->supports('foo://bar', []));
        self::assertTrue($this->buildFactory()->supports('tkpaas://bar', []));
    }
}
