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

namespace Teknoo\East\Paas\Object;

use DateTimeInterface;
use JsonSerializable;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\East\Common\Object\ObjectTrait;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

/**
 * Embedded object representing any event in a Job
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class History implements IdentifiedObjectInterface, ImmutableInterface, JsonSerializable
{
    use ObjectTrait;
    use ImmutableTrait;

    /**
     * Format to serialize date :)
     */
    final public const DATE_FORMAT = 'Y-m-d H:i:s e';

    private ?History $previous = null;

    private string $message;

    private DateTimeInterface $date;

    private bool $isFinal = false;

    /**
     * @var array<string, mixed>
     */
    private array $extra = [];

    /**
     * @param array<string, mixed> $extra
     */
    public function __construct(
        ?History $previous,
        string $message,
        DateTimeInterface $date,
        bool $isFinal = false,
        array $extra = [],
    ) {
        $this->uniqueConstructorCheck();

        $this->previous = $previous;
        $this->message = $message;
        $this->date = $date;
        $this->isFinal = $isFinal;
        $this->extra = $extra;
    }

    public function getPrevious(): ?History
    {
        return $this->previous;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getDate(): DateTimeInterface
    {
        return $this->date;
    }

    public function isFinal(): bool
    {
        return $this->isFinal;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtra(): array
    {
        return $this->extra;
    }

    /**
     * @return array<string, array<string, mixed>|bool|string|null>
     */
    public function jsonSerialize(): mixed
    {
        $previousArray = null;
        if (null !== ($previous = $this->getPrevious())) {
            $previousArray = $previous->jsonSerialize();
        }

        return [
            'message' => $this->getMessage(),
            'date' => $this->date->format(self::DATE_FORMAT),
            'is_final' => $this->isFinal(),
            'extra' => $this->getExtra(),
            'previous' => $previousArray,
        ];
    }

    public function clone(?History $newHistory): History
    {
        $history = clone $this;
        $history->date = clone $history->date;

        if (null === $newHistory) {
            return $history;
        }

        if ($newHistory->date <= $this->date) {
            $history->previous = $newHistory->clone($history->previous);

            return $history;
        }

        return $newHistory->clone($this);
    }
}
