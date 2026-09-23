<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Contracts\Hook;

use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * Interface dedicated to hooks running an external process with a timeout, to check this timeout against the worker's
 * time limit: a timeout bigger than the worker's time limit is useless, the worker will be stopped before.
 * The hook must call `success` on the promise when its timeout is compatible with the time limit (lower or equal, or
 * no timeout), else `fail` with an exception describing the issue.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
interface TimeoutAwareHookInterface extends HookInterface
{
    /**
     * @param PromiseInterface<mixed, mixed> $promise
     */
    public function checkTimeLimit(int $timeLimit, PromiseInterface $promise): TimeoutAwareHookInterface;
}
