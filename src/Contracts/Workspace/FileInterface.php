<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Contracts\Workspace;

use Teknoo\Immutable\ImmutableInterface;

interface FileInterface extends ImmutableInterface
{
    /**
     * @const  VISIBILITY_PUBLIC  public visibility
     */
    public const VISIBILITY_PUBLIC = 'public';

    /**
     * @const  VISIBILITY_PRIVATE  private visibility
     */
    public const VISIBILITY_PRIVATE = 'private';

    public function getName(): string;

    public function getVisibility(): string;

    public function getContent(): string;
}
