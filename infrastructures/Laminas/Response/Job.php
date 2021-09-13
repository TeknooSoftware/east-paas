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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Laminas\Response;

use Laminas\Diactoros\MessageTrait;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\StreamInterface;
use Teknoo\East\Paas\Contracts\Response\JobInterface;
use Teknoo\East\Paas\Object\Job as BaseJob;
use Teknoo\Immutable\ImmutableTrait;

/**
 * Response representing a job entry instance, compliant with East's response interface and
 * Clients instances
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Job implements
    JobInterface,
    PsrResponse
{
    use ImmutableTrait;
    use MessageTrait;

    private int $statusCode;

    private string $reasonPhrase;

    private BaseJob $job;

    private string $jobSerialized;

    /**
     * @param array<string, mixed> $headers
     */
    public function __construct(
        int $statusCode,
        string $reasonPhrase,
        BaseJob $job,
        string $jobSerialized,
        string|StreamInterface $body = 'php://memory',
        array $headers = []
    ) {
        $this->uniqueConstructorCheck();

        $this->reasonPhrase = $reasonPhrase;
        $this->statusCode = $statusCode;
        $this->job = $job;
        $this->jobSerialized = $jobSerialized;

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
