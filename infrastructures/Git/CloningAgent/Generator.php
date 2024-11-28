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
use Teknoo\East\Paas\Infrastructures\Git\Exception\GeneratorStateException;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * State for the class CloningAgent for the mother instance present into container, to build new Conductor instance via
 * a self cloning.
 *
 * @mixin CloningAgent
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Generator implements StateInterface
{
    use StateTrait;

    private function getWorkspace(): Closure
    {
        return function (): never {
            throw new GeneratorStateException('Agent is in generator state');
        };
    }

    private function getSourceRepository(): Closure
    {
        return function (): never {
            throw new GeneratorStateException('Agent is in generator state');
        };
    }
}
