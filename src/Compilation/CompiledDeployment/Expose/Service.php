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

namespace Teknoo\East\Paas\Compilation\CompiledDeployment\Expose;

use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

/**
 * Immutable value object, representing a normalized configuration about Service in a deployment to expose some pod
 * via a service (internal or available on the external host). Only TCP or UDP ports.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Service implements ImmutableInterface
{
    use ImmutableTrait;

    /**
     * @param array<int, int> $ports
     */
    public function __construct(
        private readonly string $name,
        private readonly string $podName,
        private readonly array $ports,
        private readonly Transport $protocol,
        private readonly bool $internal
    ) {
        $this->uniqueConstructorCheck();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPodName(): string
    {
        return $this->podName;
    }

    /**
     * @return array<int, int>
     */
    public function getPorts(): array
    {
        return $this->ports;
    }

    public function getProtocol(): Transport
    {
        return $this->protocol;
    }

    public function isInternal(): bool
    {
        return $this->internal;
    }
}
