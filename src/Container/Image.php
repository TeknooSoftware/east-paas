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

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Image implements ImmutableInterface
{
    use ImmutableTrait;

    private string $name;

    private ?string $url = null;

    private string $path;

    private bool $library;

    private ?string $tag;

    /**
     * @var array<string, mixed>
     */
    private array $variables;

    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(string $name, string $path, bool $library, ?string $tag, array $variables)
    {
        $this->uniqueConstructorCheck();

        $this->name = $name;
        $this->path = $path;
        $this->library = $library;
        $this->tag = $tag;
        $this->variables = $variables;
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

    public function getPath(): string
    {
        return $this->path;
    }

    public function isLibrary(): bool
    {
        return $this->library;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    /**
     * @return array<string, mixed>
     */
    public function getVariables(): array
    {
        return $this->variables;
    }
}
