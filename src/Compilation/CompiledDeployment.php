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

namespace Teknoo\East\Paas\Compilation;

use DomainException;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Ingress;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Secret;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Service;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuildableInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\PopulatedVolumeInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\VolumeInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;

use function is_string;
use function str_contains;

/**
 * Summary object grouping normalized instructions and states of a deployment. Understable by adapters and clusters's
 * drivers.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class CompiledDeployment implements CompiledDeploymentInterface
{
    /**
     * @var array<string, Secret>
     */
    private array $secrets = [];

    /**
     * @var array<string, array<string, string|BuildableInterface>>
     */
    private array $buildables = [];

    /**
     * @var array<string, VolumeInterface>
     */
    private array $volumes = [];

    /**
     * @var array<string, HookInterface>
     */
    private array $hooks = [];

    /**
     * @var array<string, Pod>|Pod[]
     */
    private array $pods = [];

    /**
     * @var array<string, Service>
     */
    private array $services = [];

    /**
     * @var array<string, Ingress>
     */
    private array $ingresses = [];

    public function __construct(
        private readonly int $version,
        private readonly string $namespace
    ) {
    }

    public function addBuildable(BuildableInterface $buildable): CompiledDeploymentInterface
    {
        $this->buildables[$buildable->getUrl()][$buildable->getTag()] = $buildable;

        return $this;
    }

    public function updateBuildable(BuildableInterface $old, BuildableInterface $new): CompiledDeploymentInterface
    {
        $this->buildables[$old->getUrl()][$old->getTag()] = $new->getUrl();
        $this->addBuildable($new);

        return $this;
    }

    private function getBuildable(string $url, ?string $tag): BuildableInterface
    {
        if (empty($tag)) {
            $tag = 'latest';
        }

        if (!isset($this->buildables[$url][$tag])) {
            throw new DomainException("Buildable $url:$tag is not referenced");
        }

        $value = $this->buildables[$url][$tag];

        if (is_string($value)) {
            return $this->getBuildable($value, $tag);
        }

        return $value;
    }

    public function addVolume(string $name, VolumeInterface $volume): CompiledDeploymentInterface
    {
        $this->volumes[$name] = $volume;

        return $this;
    }

    public function importVolume(
        string $volumeFrom,
        string $mountPath,
        PromiseInterface $promise
    ): CompiledDeploymentInterface {
        if (!isset($this->volumes[$volumeFrom]) || !$this->volumes[$volumeFrom] instanceof Volume) {
            $promise->fail(
                new DomainException("Volume called $volumeFrom was not found volumes definition", 400)
            );

            return $this;
        }

        $promise->success($this->volumes[$volumeFrom]->import($mountPath));

        return $this;
    }

    public function addHook(string $name, HookInterface $hook): CompiledDeploymentInterface
    {
        $this->hooks[$name] = $hook;

        return $this;
    }

    public function addPod(string $name, Pod $pod): CompiledDeploymentInterface
    {
        foreach ($pod as $container) {
            $buildable = $container->getImage();
            if (str_contains($buildable, '/')) {
                //Is an external buildable
                continue;
            }

            $this->getBuildable($buildable, $version = $container->getVersion());
        }

        $this->pods[$name] = $pod;

        return $this;
    }

    public function addSecret(string $name, Secret $secret): CompiledDeploymentInterface
    {
        $this->secrets[$name] = $secret;

        return $this;
    }

    public function addService(string $name, Service $service): CompiledDeploymentInterface
    {
        $this->services[$name] = $service;

        return $this;
    }

    public function addIngress(string $name, Ingress $ingress): CompiledDeploymentInterface
    {
        $this->ingresses[$name] = $ingress;

        return $this;
    }

    public function forNamespace(callable $callback): CompiledDeploymentInterface
    {
        $callback($this->namespace);

        return $this;
    }

    public function foreachHook(callable $callback): CompiledDeploymentInterface
    {
        foreach ($this->hooks as $hook) {
            $callback($hook);
        }

        return $this;
    }

    public function foreachVolume(callable $callback): CompiledDeploymentInterface
    {
        foreach ($this->volumes as $name => $volume) {
            $callback($name, $volume, $this->namespace);
        }

        return $this;
    }

    public function foreachBuildable(callable $callback): CompiledDeploymentInterface
    {
        $processedBuildables = [];
        foreach ($this->pods as $pod) {
            foreach ($pod as $container) {
                $buildable = $container->getImage();
                $version = $container->getVersion();

                if (isset($processedBuildables[$buildable][$version])) {
                    continue;
                }

                $processedBuildables[$buildable][$version] = true;

                $callback(
                    $this->getBuildable($buildable, $version),
                    $this->namespace
                );
            }
        }

        return $this;
    }

    public function foreachSecret(callable $callback): CompiledDeploymentInterface
    {
        foreach ($this->secrets as $secret) {
            $callback($secret, $this->namespace);
        }

        return $this;
    }

    public function foreachPod(callable $callback): CompiledDeploymentInterface
    {
        foreach ($this->pods as $pod) {
            $buildables = [];
            $volumes = [];
            foreach ($pod as $container) {
                $imgName = $container->getImage();
                $imgVersion = $container->getVersion();
                $buildables[$imgName][$imgVersion] = $this->getBuildable($imgName, $imgVersion);
                foreach ($container->getVolumes() as $name => $volume) {
                    if ($volume instanceof PopulatedVolumeInterface) {
                        $volumes[$container->getName() . '_' . $name] = $this->volumes[$name];
                    } else {
                        $volumes[$container->getName() . '_' . $name] = $volume;
                    }
                }
            }

            $callback($pod, $buildables, $volumes, $this->namespace);
        }

        return $this;
    }

    public function foreachService(callable $callback): CompiledDeploymentInterface
    {
        foreach ($this->services as $service) {
            $callback($service, $this->namespace);
        }

        return $this;
    }

    public function foreachIngress(callable $callback): CompiledDeploymentInterface
    {
        foreach ($this->ingresses as $ingress) {
            $callback($ingress, $this->namespace);
        }

        return $this;
    }
}
