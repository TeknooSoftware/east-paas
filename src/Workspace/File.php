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

namespace Teknoo\East\Paas\Workspace;

use Teknoo\East\Paas\Contracts\Workspace\Visibility;
use Teknoo\Immutable\ImmutableTrait;
use Teknoo\East\Paas\Contracts\Workspace\FileInterface;

/**
 * Object representing a file in a filesystem
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class File implements FileInterface
{
    use ImmutableTrait;

    public function __construct(
        private readonly string $name,
        private readonly Visibility $visibility,
        private readonly string $content
    ) {
        $this->uniqueConstructorCheck();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVisibility(): Visibility
    {
        return $this->visibility;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
