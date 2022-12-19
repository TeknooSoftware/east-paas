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

namespace Teknoo\East\Paas\Compilation\CompiledDeployment;

use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

/**
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class HealthCheck implements ImmutableInterface
{
    use ImmutableTrait;

    /**
     * @param string[] $command
     */
    public function __construct(
        private readonly int $initialDelay,
        private readonly int $period,
        private readonly HealthCheckType $type,
        private readonly ?array $command,
        private readonly ?int $port,
        private readonly ?string $path,

    ) {
        $this->uniqueConstructorCheck();
    }

    public function getInitialDelay(): int
    {
        return $this->initialDelay;
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

    public function getType(): HealthCheckType
    {
        return $this->type;
    }

    public function getCommand(): ?array
    {
        return $this->command;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }
}
