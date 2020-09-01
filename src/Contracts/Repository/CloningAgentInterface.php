<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Contracts\Repository;

use Teknoo\Immutable\ImmutableInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

interface CloningAgentInterface extends ImmutableInterface
{
    public function configure(
        SourceRepositoryInterface $repository,
        JobWorkspaceInterface $workspace
    ): CloningAgentInterface;

    public function run(): CloningAgentInterface;

    public function cloningIntoPath(string $path): CloningAgentInterface;
}
