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

namespace Teknoo\East\Paas\Infrastructures\Laminas\Response;

use JsonSerializable;
use Laminas\Diactoros\MessageTrait;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\StreamInterface;
use Stringable;
use Teknoo\East\Paas\Contracts\Response\ErrorInterface;
use Teknoo\Immutable\ImmutableTrait;
use Throwable;

use function array_unique;
use function array_values;
use function json_encode;

/**
 * Error response instance, compliant with East's response interface and
 * Clients instances
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Error implements
    ErrorInterface,
    JsonSerializable,
    PsrResponse,
    Stringable
{
    use ImmutableTrait;
    use MessageTrait;

    /**
     * @param array<string, mixed> $headers
     */
    public function __construct(
        private int $statusCode,
        private string $reasonPhrase,
        private readonly Throwable $error,
        string|StreamInterface $body = 'php://memory',
        array $headers = []
    ) {
        $this->uniqueConstructorCheck();

        $this->stream = $this->getStream($body, 'wb+');
        $this->stream->write((string) json_encode($this, JSON_THROW_ON_ERROR));

        $headers['Content-Type'] = ['application/problem+json'];
        $this->setHeaders($headers);
    }

    public function __toString(): string
    {
        return "$this->reasonPhrase ($this->statusCode)";
    }

    public function getError(): Throwable
    {
        return $this->error;
    }

    /**
     * @return array{type: string, title: string, status: int, detail: string[]&mixed[]}
     */
    public function jsonSerialize(): array
    {
        $messages =  [];
        $firstError = $this->error;
        do {
            $messages[] = $firstError->getMessage();
        } while (null !== ($firstError = $firstError->getPrevious()));

        return [
            'type' => 'https://teknoo.software/probs/issue',
            'title' => $this->reasonPhrase,
            'status' => $this->getStatusCode(),
            'detail' => array_values(array_unique($messages)),
        ];
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    public function withStatus($code, $reasonPhrase = ''): self
    {
        $that = clone $this;
        $that->statusCode = $code;
        $that->reasonPhrase = $reasonPhrase;

        return $that;
    }
}
