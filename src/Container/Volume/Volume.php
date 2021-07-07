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

namespace Teknoo\East\Paas\Container\Volume;

use Teknoo\East\Paas\Contracts\Container\PopulatedVolumeInterface;
use Teknoo\East\Paas\Contracts\Container\RegistrableInterface;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

use function trim;

/**
 * Immutable value object, representing a normalized configuration about a Volume, to mount into pods. Volume are not
 * persistent and all data will be lost when there are no more pods using them. They are correlated to pods' lifecycles.
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Volume implements ImmutableInterface, RegistrableInterface, PopulatedVolumeInterface
{
    use ImmutableTrait;

    private string $name;

    /**
     * @var string[]
     */
    private array $paths;

    private string $localPath;

    private string $mountPath;

    private bool $isEmbedded = false;

    private ?string $registry = null;

    /**
     * @param string[] $paths
     */
    public function __construct(
        string $name,
        array $paths,
        string $localPath,
        string $mountPath,
        bool $isEmbedded = false
    ) {
        $this->uniqueConstructorCheck();

        $this->name = $name;
        $this->paths = $paths;
        $this->localPath = $localPath;
        $this->mountPath = $mountPath;
        $this->isEmbedded = $isEmbedded;
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
