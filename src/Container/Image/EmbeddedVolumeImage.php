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

namespace Teknoo\East\Paas\Container\Image;

use Teknoo\East\Paas\Contracts\Container\BuildableInterface;
use Teknoo\East\Paas\Contracts\Container\VolumeInterface;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class EmbeddedVolumeImage implements ImmutableInterface, BuildableInterface
{
    use ImmutableTrait;

    private string $name;

    private ?string $tag;

    private ?string $registry = null;

    private string $originalName;

    /**
     * @var VolumeInterface[]
     */
    private array $volumes;

    /**
     * @param VolumeInterface[] $volumes
     */
    public function __construct(
        string $name,
        string $tag,
        string $originalName,
        array $volumes
    ) {
        $this->uniqueConstructorCheck();

        $this->name = $name;
        $this->tag = $tag;
        $this->originalName = $originalName;
        $this->volumes = $volumes;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function withRegistry(string $registry): self
    {
        $that = clone $this;
        $that->registry = $registry;

        return $that;
    }

    public function getUrl(): string
    {
        return \trim($this->registry . '/' . $this->name, '/');
    }

    /**
     * @return VolumeInterface[]
     */
    public function getVolumes(): array
    {
        return $this->volumes;
    }

    public function getVariables(): array
    {
        return [];
    }

    public function getPath(): string
    {
        return '';
    }
}
