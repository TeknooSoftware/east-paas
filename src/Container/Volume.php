<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Container;

use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

class Volume implements ImmutableInterface
{
    use ImmutableTrait;

    private string $name;

    private ?string $url = null;

    private string $target;

    private ?string $mountPath = null;

    /**
     * @var string[]
     */
    private array $paths;

    /**
     * @param string[] $paths
     */
    public function __construct(string $name, string $target, array $paths)
    {
        $this->uniqueConstructorCheck();

        $this->name = $name;
        $this->target = $target;
        $this->paths = $paths;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function updateUrl(string $url): self
    {
        $that = clone $this;
        $that->url = $url;

        return $that;
    }

    public function getUrl(): string
    {
        return \trim($this->url . '/' . $this->name, '/');
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function updateMountPath(string $mountPath): self
    {
        $that = clone $this;
        $that->mountPath = $mountPath;

        return $that;
    }

    public function getMountPath(): string
    {
        if (null === $this->mountPath) {
            return $this->getTarget();
        }

        return $this->mountPath;
    }

    /**
     * @return string[]
     */
    public function getPaths(): array
    {
        return $this->paths;
    }
}
