<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Contracts\Hook;

use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * To define service, to call before any deployments to perform some actions to prepare it
 * (like download and install vendors or any other dependencies, compile some stuff, etc).
 * An Hook can be configured from the PaaS file.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/states Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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
