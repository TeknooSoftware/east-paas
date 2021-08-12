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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Contracts\Conductor;

use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Ingress;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Service;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Secret;
use Teknoo\East\Paas\Contracts\Container\BuildableInterface;
use Teknoo\East\Paas\Contracts\Container\VolumeInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;

/**
 * To define object able to grouping normalized instructions and states of a deployment. Understable by adapters and
 * clusters's drivers.
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
interface CompiledDeploymentInterface
{
    public function addBuildable(BuildableInterface $buildable): CompiledDeploymentInterface;

    public function updateBuildable(BuildableInterface $old, BuildableInterface $new): CompiledDeploymentInterface;

    public function addVolume(string $name, VolumeInterface $volume): CompiledDeploymentInterface;

    public function importVolume(
        string $volumeFrom,
        string $mountPath,
        PromiseInterface $promise
    ): CompiledDeploymentInterface;

    public function addHook(string $name, HookInterface $hook): CompiledDeploymentInterface;

    public function addPod(string $name, Pod $pod): CompiledDeploymentInterface;

    public function addSecret(string $name, Secret $secret): CompiledDeploymentInterface;

    public function addService(string $name, Service $service): CompiledDeploymentInterface;

    public function addIngress(string $name, Ingress $ingress): CompiledDeploymentInterface;

    public function foreachHook(callable $callback): CompiledDeploymentInterface;

    public function foreachVolume(callable $callback): CompiledDeploymentInterface;

    public function foreachBuildable(callable $callback): CompiledDeploymentInterface;

    public function foreachSecret(callable $callback): CompiledDeploymentInterface;

    public function foreachPod(callable $callback): CompiledDeploymentInterface;

    public function foreachService(callable $callback): CompiledDeploymentInterface;

    public function foreachIngress(callable $callback): CompiledDeploymentInterface;
}
