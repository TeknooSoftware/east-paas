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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Compilation\CompiledDeployment\Image;

use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuildableInterface;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

use function trim;

/**
 * Immutable value object, representing a normalized configuration about container OCI Image, to use for a pod.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Image implements ImmutableInterface, BuildableInterface
{
    use ImmutableTrait;

    private ?string $registry = null;

    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(
        private readonly string $name,
        private readonly string $path,
        private readonly bool $library,
        private readonly ?string $tag,
        private readonly array $variables
    ) {
        $this->uniqueConstructorCheck();
    }

    public function getName(): string
    {
        return $this->name;
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
