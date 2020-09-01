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
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Transport;

use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class TransportFactory implements TransportFactoryInterface
{
    private GuzzleTransport $guzzleTransport;

    private string $protocol;

    public function __construct(GuzzleTransport $guzzleTransport, string $protocol)
    {
        $this->guzzleTransport = $guzzleTransport;
        $this->protocol = $protocol;
    }

    /**
     * @return string[]
     */
    private function extractUri(string $dsn): array
    {
        $params = \substr($dsn, \strlen($this->protocol));
        return \explode(':', $params);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        if (0 !== strpos($dsn, $this->protocol)) {
            throw new \RuntimeException("The $dsn is not managed by this transport");
        }

        $transport = clone $this->guzzleTransport;

        list($method, $uri) = $this->extractUri($dsn);
        $transport->configure($method, 'https://' . $uri);

        return $transport;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function supports(string $dsn, array $options): bool
    {
        return 0 === strpos($dsn, $this->protocol);
    }
}
