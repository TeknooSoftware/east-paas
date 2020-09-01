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
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
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
