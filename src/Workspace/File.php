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

namespace Teknoo\East\Paas\Workspace;

use Teknoo\Immutable\ImmutableTrait;
use Teknoo\East\Paas\Contracts\Workspace\FileInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
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