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

namespace Teknoo\Tests\East\Paas\Behat\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\HistorySent;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\JobDone;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\MessageJob;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Parameter;

use Teknoo\Tests\East\Paas\Behat\FeatureContext;
use function is_array;
use function json_decode;
use function str_replace;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
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

        return str_replace($names, $values, $this->uri);
    }

    public function send(Envelope $envelope): Envelope
    {
        $message = $envelope->getMessage();

        FeatureContext::$messageByTypeIsEncrypted[$message::class] = match ($message::class) {
            MessageJob::class => !is_array(json_decode($message->getMessage(), associative: true)),
            JobDone::class => !is_array(json_decode($message->getMessage(), associative: true)),
            HistorySent::class => !is_array(json_decode($message->getMessage(), associative: true)),
            default => false,
        } || (FeatureContext::$messageByTypeIsEncrypted[$message::class] ?? false);

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
        throw new LogicException('Not implemented');
    }

    public function stop(): void
    {
        throw new LogicException('Not implemented');
    }

    public function get(): iterable
    {
        throw new LogicException('Not implemented');
    }

    public function ack(Envelope $envelope): void
    {
        throw new LogicException('Not implemented');
    }

    public function reject(Envelope $envelope): void
    {
        throw new LogicException('Not implemented');
    }
}
