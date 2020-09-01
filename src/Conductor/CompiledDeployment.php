<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Conductor;

use Teknoo\East\Paas\Container\Service;
use Teknoo\East\Paas\Container\Volume;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\East\Paas\Container\Image;
use Teknoo\East\Paas\Container\Pod;

class CompiledDeployment
{
    /**
     * @var array<string, array<string, Image>>
     */
    private array $images = [];
    
    /**
     * @var array<string, Volume>
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

    public function addImage(Image $image): self
    {
        $this->images[$image->getName()][$image->getTag()] = $image;

        return $this;
    }

    public function updateImage(Image $old, Image $new): self
    {
        $this->images[$old->getName()][$old->getTag()] = $new;

        return $this;
    }

    public function defineVolume(string $name, Volume $volume): self
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
            if (!isset($this->images[$image = $container->getImage()][$version = $container->getVersion()])) {
                throw new \DomainException("Image $image:$version is not available");
            }
        }

        $this->pods[$name] = $pod;

        return $this;
    }

    public function addService(string $name, Service $service): self
    {
        $this->services[$name] = $service;

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

    public function foreachImage(callable $callback): self
    {
        $processedImages = [];
        foreach ($this->pods as $pod) {
            foreach ($pod as $container) {
                $image = $container->getImage();
                $version = $container->getVersion();

                if (isset($processedImages[$image][$version])) {
                    continue;
                }

                $processedImages[$image][$version] = true;

                $callback(
                    $this->images[$image][$version],
                );
            }
        }

        return $this;
    }

    public function foreachPod(callable $callback): self
    {
        foreach ($this->pods as $pod) {
            $images = [];
            $volumes = [];
            foreach ($pod as $container) {
                $imgName = $container->getImage();
                $imgVersion = $container->getVersion();
                $images[$imgName][$imgVersion] = $this->images[$imgName][$imgVersion];

                foreach ($container->getVolumes() as $volumeName) {
                    $volumes[$volumeName] = $this->volumes[$volumeName];
                }
            }

            $callback($pod, $images, $volumes);
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
}
