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
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Compilation\CompiledDeployment\Volume;

use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\PopulatedVolumeInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\RegistrableInterface;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

use function trim;

/**
 * Immutable value object, representing a normalized configuration about a Volume, to mount into pods. Volume are not
 * persistent and all data will be lost when there are no more pods using them. They are correlated to pods' lifecycles.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Volume implements ImmutableInterface, RegistrableInterface, PopulatedVolumeInterface
{
    use ImmutableTrait;

    private ?string $registry = null;

    /**
     * @param string[] $paths
     * @param string[] $writables
     */
    public function __construct(
        private readonly string $name,
        private readonly array $paths,
        private readonly string $localPath,
        private string $mountPath,
        private readonly array $writables = [],
        private readonly bool $isEmbedded = false
    ) {
        $this->uniqueConstructorCheck();
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * @return string[]
     */
    public function getWritables(): array
    {
        return $this->writables;
    }

    public function getLocalPath(): string
    {
        return $this->localPath;
    }

    public function getMountPath(): string
    {
        return $this->mountPath;
    }

    public function isEmbedded(): bool
    {
        return $this->isEmbedded;
    }

    public function import(string $mountPath): PopulatedVolumeInterface
    {
        $volume = clone $this;
        $volume->mountPath = $mountPath;

        return $volume;
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
}
