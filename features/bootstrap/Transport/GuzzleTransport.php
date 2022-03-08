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

namespace Teknoo\Tests\Behat\Transport;

use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Parameter;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class GuzzleTransport implements TransportInterface
{
    private Client $guzzle;

    private LoggerInterface $logger;

    private string $method;

    private string $uri;

    public function __construct(Client $guzzle, LoggerInterface $logger)
    {
        $this->guzzle = $guzzle;
        $this->logger = $logger;
    }

    public function __clone()
    {
        $this->guzzle = clone $this->guzzle;
    }

    public function configure(string $method, string $uri): self
    {
        $this->method = $method;
        $this->uri = $uri;

        return $this;
    }

    private function getUri(Envelope $envelope): string
    {
        $names = [];
        $values = [];

        /** @var Parameter $param */
        foreach ($envelope->all(Parameter::class) as $param) {
            $names[] = $param->getName();
            $values[] = $param->getValue();
        }

        return \str_replace($names, $values, $this->uri);
    }

    public function send(Envelope $envelope): Envelope
    {
        $message = $envelope->getMessage();
        $uri = $this->getUri($envelope);

        $promise = $this->guzzle->requestAsync($this->method, $uri, ['body' => (string) $message]);
        $promise->then(
            function () {
            },
            function (RequestException $error) {
                $this->logger->error($error->getMessage());
            }
        );

        return $envelope;
    }

    public function receive(callable $handler): void
    {
        throw new \LogicException('Not implemented');
    }

    public function stop(): void
    {
        throw new \LogicException('Not implemented');
    }

    public function get(): iterable
    {
        throw new \LogicException('Not implemented');
    }

    public function ack(Envelope $envelope): void
    {
        throw new \LogicException('Not implemented');
    }

    public function reject(Envelope $envelope): void
    {
        throw new \LogicException('Not implemented');
    }
}
