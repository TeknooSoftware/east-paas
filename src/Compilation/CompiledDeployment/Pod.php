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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Compilation\CompiledDeployment;

use IteratorAggregate;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\Reference;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;
use Traversable;

/**
 * Immutable value object, representing a normalized Pod, depoyable unit, grouping at least one
 * container, having shared storage / network, and a specification on how to run these containers. The elements of a
 * pod are always co-located and co-ordered, and run in a shared context.
 * Pods can being replicated more than once times.
 *
 * @implements IteratorAggregate<Container>
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Pod implements ImmutableInterface, IteratorAggregate
{
    use ImmutableTrait;

    /**
     * @param array<int, Container> $containers
     * @param string[] $requires
     */
    public function __construct(
        private readonly string $name,
        private readonly int $replicas,
        private readonly array $containers,
        private readonly string|Reference|null $ociRegistryConfigName = null,
        private readonly int $maxUpgradingPods = 1,
        private readonly int $maxUnavailablePods = 0,
        private readonly UpgradeStrategy $upgradeStrategy = UpgradeStrategy::RollingUpgrade,
        private readonly ?int $fsGroup = null,
        private readonly array $requires = [],
        private readonly bool $isStateless = true,
    ) {
        $this->uniqueConstructorCheck();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getReplicas(): int
    {
        return $this->replicas;
    }

    /**
     * @return Traversable<Container>
     */
    public function getIterator(): Traversable
    {
        yield from $this->containers;
    }

    public function getOciRegistryConfigName(): string|Reference|null
    {
        return $this->ociRegistryConfigName;
    }

    public function getMaxUpgradingPods(): int
    {
        return $this->maxUpgradingPods;
    }

    public function getMaxUnavailablePods(): int
    {
        return $this->maxUnavailablePods;
    }

    public function getUpgradeStrategy(): UpgradeStrategy
    {
        return $this->upgradeStrategy;
    }

    public function getFsGroup(): ?int
    {
        return $this->fsGroup;
    }

    /**
     * @return string[]
     */
    public function getRequires(): array
    {
        return $this->requires;
    }

    public function isStateless(): bool
    {
        return $this->isStateless;
    }
}
