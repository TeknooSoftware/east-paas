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
 * To define service, to call before any deployments to perform some actions to prepare it
 * (like download and install vendors or any other dependencies, compile some stuff, etc).
 * An Hook can be configured from the PaaS file.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
interface HookInterface
{
    public function setPath(string $path): HookInterface;

    /**
     * @param array<string, mixed> $options
     * @param PromiseInterface<mixed, mixed> $promise
     */
    public function setOptions(array $options, PromiseInterface $promise): HookInterface;

    /**
     * @param PromiseInterface<string, mixed> $promise
     */
    public function run(PromiseInterface $promise): HookInterface;
}
