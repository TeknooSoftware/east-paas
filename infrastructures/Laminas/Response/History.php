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

namespace Teknoo\East\Paas\Infrastructures\Laminas\Response;

use JsonSerializable;
use Laminas\Diactoros\MessageTrait;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\StreamInterface;
use Stringable;
use Teknoo\East\Paas\Contracts\Response\HistoryInterface;
use Teknoo\East\Paas\Object\History as BaseHistory;
use Teknoo\Immutable\ImmutableTrait;

use function json_encode;

/**
 * Response representing a history entry instance, compliant with East's response interface and
 * Clients instances.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class History implements
    HistoryInterface,
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
        private readonly BaseHistory $history,
        string|StreamInterface $body = 'php://memory',
        array $headers = []
    ) {
        $this->uniqueConstructorCheck();

        $this->stream = $this->getStream($body, 'wb+');
        $this->stream->write((string) json_encode($this->history, JSON_THROW_ON_ERROR));

        $headers['Content-Type'] = ['application/json'];
        $this->setHeaders($headers);
    }

    public function __toString(): string
    {
        return $this->history->getMessage();
    }

    public function getHistory(): BaseHistory
    {
        return $this->history;
    }

    /**
     * @return array<string, array<string, mixed>|bool|string|int|null>
     */
    public function jsonSerialize(): array
    {
        return $this->history->jsonSerialize();
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
