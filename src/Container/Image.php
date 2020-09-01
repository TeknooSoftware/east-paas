<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Container;

use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

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
