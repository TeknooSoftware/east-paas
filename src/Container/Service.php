<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
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

namespace Teknoo\East\Paas\Container;

use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
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
