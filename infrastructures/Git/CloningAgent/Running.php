<?php

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

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Git\CloningAgent;

use Closure;
use Teknoo\East\Paas\Infrastructures\Git\CloningAgent;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Object\GitRepository;
use Teknoo\East\Paas\Object\SshIdentity;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * State for the class CloningAgent for the daughter instance present into the workplan
 *
 * @mixin CloningAgent
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Running implements StateInterface
{
    use StateTrait;

    private function getWorkspace(): Closure
    {
        return function (): JobWorkspaceInterface {
            return $this->workspace;
        };
    }

    private function getSshIdentity(): Closure
    {
        return function (): SshIdentity {
            return $this->sshIdentity;
        };
    }

    private function getSourceRepository(): Closure
    {
        return function (): GitRepository {
            return $this->sourceRepository;
        };
    }
}
