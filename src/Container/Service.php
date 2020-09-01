<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Container;

use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

class Service implements ImmutableInterface
{
    use ImmutableTrait;

    public const TCP = 'TCP';
    public const UDP = 'UDP';

    private string $name;

    /**
     * @var array<int, int>
     */
    private array $ports;

    private string $protocol;

    /**
     * @param array<int, int> $ports
     */
    public function __construct(string $name, array $ports, string $protocol)
    {
        $this->uniqueConstructorCheck();

        $this->name = $name;
        $this->ports = $ports;
        $this->protocol = $protocol;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<int, int>
     */
    public function getPorts(): array
    {
        return $this->ports;
    }

    public function getProtocol(): string
    {
        return $this->protocol;
    }
}
