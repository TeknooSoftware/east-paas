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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
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
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
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

    private int $serialNumber = 0;

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
        int $serialNumber = 0,
    ) {
        $this->uniqueConstructorCheck();

        $this->previous = $previous;
        $this->message = $message;
        $this->date = $date;
        $this->isFinal = $isFinal;
        $this->extra = $extra;
        $this->serialNumber = $serialNumber;
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

    public function getSerialNumber(): int
    {
        return $this->serialNumber;
    }

    /**
     * @return array<string, array<string, mixed>|bool|string|int|null>
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
            'serial_number' => $this->getSerialNumber(),
        ];
    }

    public function clone(?History $newHistory): History
    {
        $currentHistory = clone $this;
        $currentHistory->date = clone $currentHistory->date;

        if (null === $newHistory) {
            return $currentHistory;
        }

        if (
            true === $currentHistory->isFinal //The current is Final,
            || (
                false === $newHistory->isFinal
                && (
                    $newHistory->date <= $currentHistory->date //The current's is more recent
                    || (
                        $newHistory->date == $currentHistory->date
                        && $newHistory->serialNumber < $currentHistory->serialNumber
                    ) //The current's serial is higher
                )
            )
        ) {
            $currentHistory->previous = $newHistory->clone($currentHistory->previous);

            return $currentHistory;
        }

        $newHistory = $newHistory->clone(null);
        $newHistory->previous = $currentHistory->clone($newHistory->previous);

        return $newHistory;
    }

    public function limit(int $count): self
    {
        $that = clone $this;
        if ($count > 1) {
            $that->previous = $that->previous?->limit($count - 1);
        } else {
            $that->previous = null;
        }

        return $that;
    }
}
