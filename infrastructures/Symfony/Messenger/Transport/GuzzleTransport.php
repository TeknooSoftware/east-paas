<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Transport;

use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Parameter;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\TransportInterface;

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
