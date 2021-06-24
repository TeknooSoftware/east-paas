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

namespace Teknoo\East\Paas\Object;

use Psr\Http\Message\ResponseInterface as PsrResponse;
use Teknoo\East\Foundation\Client\ResponseInterface as EastResponse;
use Teknoo\East\Website\Contracts\ObjectInterface;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;
use Throwable;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Error implements
    ObjectInterface,
    ImmutableInterface,
    EastResponse,
    \JsonSerializable,
    PsrResponse
{
    use ImmutableTrait;

    private string $message;

    private int $httpCode;

    private Throwable $error;

    public function __construct(string $message, int $httpCode, Throwable $error)
    {
        $this->uniqueConstructorCheck();

        $this->message = $message;
        $this->httpCode = $httpCode;
        $this->error = $error;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function getError(): Throwable
    {
        return $this->error;
    }

    public function __toString(): string
    {
        return "{$this->message} ({$this->httpCode})";
    }

    public function jsonSerialize()
    {
        return [
            'type' => 'https://teknoo.software/probs/issue',
            'title' => $this->message,
            'status' => $this->getMessage(),
            'detail' => $this->error->getMessage(),
        ];
    }
}
