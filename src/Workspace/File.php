<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Workspace;

use Teknoo\Immutable\ImmutableTrait;
use Teknoo\East\Paas\Contracts\Workspace\FileInterface;

class File implements FileInterface
{
    use ImmutableTrait;

    private string $name;

    private string $visibility;

    private string $content;

    public function __construct(string $name, string $visibility, string $content)
    {
        $this->uniqueConstructorCheck();

        $this->name = $name;
        $this->visibility = $visibility;
        $this->content = $content;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
