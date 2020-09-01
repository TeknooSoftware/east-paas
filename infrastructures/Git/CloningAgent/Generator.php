<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Git\CloningAgent;

use Teknoo\East\Paas\Infrastructures\Git\CloningAgent;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Object\GitRepository;
use Teknoo\East\Paas\Object\SshIdentity;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * @mixin CloningAgent
 */
class Generator implements StateInterface
{
    use StateTrait;

    private function getWorkspace(): \Closure
    {
        return function (): JobWorkspaceInterface {
            throw new \RuntimeException('Agent is in generator state');
        };
    }

    private function getSshIdentity(): \Closure
    {
        return function (): SshIdentity {
            throw new \RuntimeException('Agent is in generator state');
        };
    }

    private function getSourceRepository(): \Closure
    {
        return function (): GitRepository {
            throw new \RuntimeException('Agent is in generator state');
        };
    }
}
