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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Infrastructures\Laminas\Response;

use Laminas\Diactoros\MessageTrait;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\StreamInterface;
use Stringable;
use Teknoo\East\Paas\Contracts\Response\JobInterface;
use Teknoo\East\Paas\Object\Job as BaseJob;
use Teknoo\Immutable\ImmutableTrait;

/**
 * Response representing a job entry instance, compliant with East's response interface and
 * Clients instances
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Job implements
    JobInterface,
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
        private readonly BaseJob $job,
        private readonly string $jobSerialized,
        string|StreamInterface $body = 'php://memory',
        array $headers = []
    ) {
        $this->uniqueConstructorCheck();

        $this->stream = $this->getStream($body, 'wb+');
        $this->stream->write($this->jobSerialized);

        $headers['Content-Type'] = ['application/json'];
        $this->setHeaders($headers);
    }

    public function __toString(): string
    {
        return $this->jobSerialized;
    }

    public function getJob(): BaseJob
    {
        return $this->job;
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
