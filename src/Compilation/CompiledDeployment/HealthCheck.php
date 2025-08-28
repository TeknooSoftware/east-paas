<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Compilation\CompiledDeployment;

use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

/**
 * Immutable value object, representing a healtcheck configuration to detect when a container is crashed
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
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
        private readonly ?bool $isSecure,
        private readonly int $successThreshold,
        private readonly int $failureThreshold,
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

    /**
     * @return string[]|null
     */
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

    public function isSecure(): ?bool
    {
        return $this->isSecure;
    }

    public function getSuccessThreshold(): int
    {
        return $this->successThreshold;
    }

    public function getFailureThreshold(): int
    {
        return $this->failureThreshold;
    }
}
