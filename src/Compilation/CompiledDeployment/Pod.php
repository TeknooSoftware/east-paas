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

use IteratorAggregate;
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
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Pod implements ImmutableInterface, IteratorAggregate
{
    use ImmutableTrait;

    /**
     * @param array<int, Container> $containers
     */
    public function __construct(
        private readonly string $name,
        private readonly int $replicas,
        private readonly array $containers,
        private readonly ?string $ociRegistryConfigName = null,
        private readonly int $maxUpgradingPods = 1,
        private readonly int $maxUnavailablePods = 0,
        private readonly UpgradeStrategy $upgradeStrategy = UpgradeStrategy::RollingUpgrade,
        private readonly ?int $fsGroup = null,
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

    public function getOciRegistryConfigName(): ?string
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
}
