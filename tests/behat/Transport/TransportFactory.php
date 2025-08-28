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

namespace Teknoo\Tests\East\Paas\Behat\Transport;

use RuntimeException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

use function explode;
use function strlen;
use function strpos;
use function substr;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class TransportFactory implements TransportFactoryInterface
{
    private readonly GuzzleTransport $guzzleTransport;

    private readonly string $protocol;

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
        $params = substr($dsn, strlen($this->protocol));
        return explode(':', $params);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        if (!str_starts_with($dsn, $this->protocol)) {
            throw new RuntimeException("The $dsn is not managed by this transport");
        }

        $transport = clone $this->guzzleTransport;

        [$method, $uri] = $this->extractUri($dsn);
        $transport->configure($method, 'https://' . $uri);

        return $transport;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function supports(string $dsn, array $options): bool
    {
        return str_starts_with($dsn, $this->protocol);
    }
}
