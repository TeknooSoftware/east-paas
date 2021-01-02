<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
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

namespace Teknoo\East\Paas\Conductor;

use Teknoo\East\Paas\Container\Expose\Ingress;
use Teknoo\East\Paas\Container\Secret;
use Teknoo\East\Paas\Container\Expose\Service;
use Teknoo\East\Paas\Contracts\Container\BuildableInterface;
use Teknoo\East\Paas\Contracts\Container\PopulatedVolumeInterface;
use Teknoo\East\Paas\Contracts\Container\VolumeInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\East\Paas\Container\Pod;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class CompiledDeployment
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

    public function addBuildable(BuildableInterface $buildable): self
    {
        $this->buildables[$buildable->getUrl()][$buildable->getTag()] = $buildable;

        return $this;
    }

    public function updateBuildable(BuildableInterface $old, BuildableInterface $new): self
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
            throw new \DomainException("Buildable $url:$tag is not referenced");
        }

        $value = $this->buildables[$url][$tag];

        if (\is_string($value)) {
            return $this->getBuildable($value, $tag);
        }

        return $value;
    }

    public function defineVolume(string $name, VolumeInterface $volume): self
    {
        $this->volumes[$name] = $volume;

        return $this;
    }

    public function defineHook(string $name, HookInterface $hook): self
    {
        $this->hooks[$name] = $hook;

        return $this;
    }

    public function addPod(string $name, Pod $pod): self
    {
        foreach ($pod as $container) {
            $buildable = $container->getImage();
            if (false !== \strpos($buildable, '/')) {
                //Is an external buildable
                continue;
            }

            $this->getBuildable($buildable, $version = $container->getVersion());
        }

        $this->pods[$name] = $pod;

        return $this;
    }

    public function addSecret(string $name, Secret $secret): self
    {
        $this->secrets[$name] = $secret;

        return $this;
    }

    public function addService(string $name, Service $service): self
    {
        $this->services[$name] = $service;

        return $this;
    }

    public function addIngress(string $name, Ingress $ingress): self
    {
        $this->ingresses[$name] = $ingress;

        return $this;
    }

    public function foreachHook(callable $callback): self
    {
        foreach ($this->hooks as $hook) {
            $callback($hook);
        }

        return $this;
    }

    public function foreachVolume(callable $callback): self
    {
        foreach ($this->volumes as $name => $volume) {
            $callback($name, $volume);
        }

        return $this;
    }

    public function foreachSecret(callable $callback): self
    {
        foreach ($this->secrets as $secret) {
            $callback($secret);
        }

        return $this;
    }

    public function foreachBuildable(callable $callback): self
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
                    $this->getBuildable($buildable, $version)
                );
            }
        }

        return $this;
    }

    public function foreachPod(callable $callback): self
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

            $callback($pod, $buildables, $volumes);
        }

        return $this;
    }

    public function foreachService(callable $callback): self
    {
        foreach ($this->services as $service) {
            $callback($service);
        }

        return $this;
    }

    public function foreachIngress(callable $callback): self
    {
        foreach ($this->ingresses as $ingress) {
            $callback($ingress);
        }

        return $this;
    }
}
