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

namespace Teknoo\East\Paas\Compilation\CompiledDeployment\Image;

use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuildableInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\VolumeInterface;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

use function trim;

/**
  Immutable value object, representing a container image, with some directory or files imported from the source
 * repository. This Image avoid to have some pods with executables and some other pods only dedicated to share
 * theirs volumes to firsts pods.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class EmbeddedVolumeImage implements ImmutableInterface, BuildableInterface
{
    use ImmutableTrait;

    private ?string $registry = null;

    /**
     * @param VolumeInterface[] $volumes
     */
    public function __construct(
        private readonly string $name,
        private readonly string $tag,
        private readonly string $originalName,
        private readonly string $originalTag,
        private readonly array $volumes
    ) {
        $this->uniqueConstructorCheck();
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

    public function getOriginalTag(): string
    {
        return $this->originalTag;
    }

    public function withRegistry(string $registry): self
    {
        $that = clone $this;
        $that->registry = $registry;

        return $that;
    }

    public function getUrl(): string
    {
        return trim($this->registry . '/' . $this->name, '/');
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
