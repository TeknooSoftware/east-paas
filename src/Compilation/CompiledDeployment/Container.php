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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Compilation\CompiledDeployment;

use Teknoo\East\Paas\Contracts\Container\VolumeInterface;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

/**
 * Immutable value object, representing a normalized OCI container, executing an OCI image, on a pod
 * with somes volumes to mount automatically into the container, and external ports to plug to this
 * container.
 * A pod can have several containers.
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Container implements ImmutableInterface
{
    use ImmutableTrait;

    private string $name;

    private string $image;

    private ?string $version;

    /**
     * @var int[]
     */
    private array $listen;

    /**
     * @var array<string, VolumeInterface>
     */
    private array $volumes;

    /**
     * @var array<string, mixed>
     */
    private array $variables;

    /**
     * @param int[] $listen
     * @param array<string, VolumeInterface> $volumes
     * @param array<string, mixed> $variables
     */
    public function __construct(
        string $name,
        string $image,
        ?string $version,
        array $listen,
        array $volumes,
        array $variables
    ) {
        $this->uniqueConstructorCheck();

        $this->name = $name;
        $this->image = $image;
        $this->version = $version;
        $this->listen = $listen;
        $this->volumes = $volumes;
        $this->variables = $variables;
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
}
