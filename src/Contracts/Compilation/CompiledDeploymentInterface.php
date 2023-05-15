<?php

declare(strict_types=1);

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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Contracts\Compilation;

use Teknoo\East\Paas\Compilation\CompiledDeployment\Map;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Ingress;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Service;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Secret;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuildableInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\VolumeInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;

/**
 * To define object able to grouping normalized instructions and states of a deployment. Understable by adapters and
 * clusters's drivers.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
interface CompiledDeploymentInterface
{
    public function addBuildable(BuildableInterface $buildable): CompiledDeploymentInterface;

    public function updateBuildable(BuildableInterface $old, BuildableInterface $new): CompiledDeploymentInterface;

    public function addVolume(string $name, VolumeInterface $volume): CompiledDeploymentInterface;

    /**
     * @param PromiseInterface<VolumeInterface, mixed> $promise
     */
    public function importVolume(
        string $volumeFrom,
        string $mountPath,
        PromiseInterface $promise
    ): CompiledDeploymentInterface;

    public function addHook(string $name, HookInterface $hook): CompiledDeploymentInterface;

    public function addPod(string $name, Pod $pod): CompiledDeploymentInterface;

    public function addSecret(string $name, Secret $secret): CompiledDeploymentInterface;

    public function addMap(string $name, Map $map): CompiledDeploymentInterface;

    public function addService(string $name, Service $service): CompiledDeploymentInterface;

    public function addIngress(string $name, Ingress $ingress): CompiledDeploymentInterface;

    public function forNamespace(callable $callback): CompiledDeploymentInterface;

    public function foreachHook(callable $callback): CompiledDeploymentInterface;

    public function foreachVolume(callable $callback): CompiledDeploymentInterface;

    public function foreachBuildable(callable $callback): CompiledDeploymentInterface;

    public function foreachSecret(callable $callback): CompiledDeploymentInterface;

    public function foreachMap(callable $callback): CompiledDeploymentInterface;

    public function foreachPod(callable $callback): CompiledDeploymentInterface;

    public function foreachService(callable $callback): CompiledDeploymentInterface;

    public function foreachIngress(callable $callback): CompiledDeploymentInterface;
}
