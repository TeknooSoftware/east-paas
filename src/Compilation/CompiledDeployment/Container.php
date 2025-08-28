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

use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\VolumeInterface;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

/**
 * Immutable value object, representing a normalized OCI container, executing an OCI image, on a pod
 * with somes volumes to mount automatically into the container, and external ports to plug to this
 * container.
 * A pod can have several containers.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Container implements ImmutableInterface
{
    use ImmutableTrait;

    /**
     * @param int[] $listen
     * @param array<string, VolumeInterface> $volumes
     * @param array<string, mixed> $variables
     */
    public function __construct(
        private readonly string $name,
        private readonly string $image,
        private readonly ?string $version,
        private readonly array $listen = [],
        private readonly array $volumes = [],
        private readonly array $variables = [],
        private readonly ?HealthCheck $healthCheck = null,
        private readonly ResourceSet $resources = new ResourceSet([]),
    ) {
        $this->uniqueConstructorCheck();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * @return int[]
     */
    public function getListen(): array
    {
        return $this->listen;
    }

    /**
     * @return array<string, VolumeInterface>
     */
    public function getVolumes(): array
    {
        return $this->volumes;
    }

    /**
     * @return array<string, mixed>
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    public function getHealthCheck(): ?HealthCheck
    {
        return $this->healthCheck;
    }

    public function getResources(): ResourceSet
    {
        return $this->resources;
    }
}
