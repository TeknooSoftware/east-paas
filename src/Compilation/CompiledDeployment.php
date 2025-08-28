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

namespace Teknoo\East\Paas\Compilation;

use DomainException;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Ingress;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Service;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Map;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Secret;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuildableInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\PopulatedVolumeInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\VolumeInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\Recipe\Promise\PromiseInterface;

use function is_string;

/**
 * Summary object grouping normalized instructions and states of a deployment. Understable by adapters and clusters's
 * drivers.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class CompiledDeployment implements CompiledDeploymentInterface
{
    /**
     * @var array<string, Secret>
     */
    private array $secrets = [];

    /**
     * @var array<string, Map>
     */
    private array $maps = [];

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
     * @var array<string, Job>|Job[]
     */
    private array $jobs = [];

    /**
     * @var array<string, Service>
     */
    private array $services = [];

    /**
     * @var array<string, Ingress>
     */
    private array $ingresses = [];

    private ?DefaultsBag $defaultsBag = null;

    public function __construct(
        private readonly float $version,
        private readonly ?string $prefix,
        private readonly ?string $projectName,
    ) {
    }

    public function getVersion(): float
    {
        return $this->version;
    }

    public function setDefaultBags(DefaultsBag $bag): CompiledDeploymentInterface
    {
        $this->defaultsBag = $bag;

        return $this;
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

    private function hasBuildable(string $url, ?string $tag): bool
    {
        if (empty($tag)) {
            $tag = 'latest';
        }

        if (!isset($this->buildables[$url][$tag])) {
            return false;
        }

        $value = $this->buildables[$url][$tag];

        if (is_string($value)) {
            return $this->hasBuildable($value, $tag);
        }

        return true;
    }

    private function getBuildable(string $url, ?string $tag): BuildableInterface
    {
        if (empty($tag)) {
            $tag = 'latest';
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
        $this->pods[$name] = $pod;

        return $this;
    }

    public function addJob(string $name, Job $jod): CompiledDeploymentInterface
    {
        $this->jobs[$name] = $jod;

        return $this;
    }

    public function addSecret(string $name, Secret $secret): CompiledDeploymentInterface
    {
        $this->secrets[$name] = $secret;

        return $this;
    }

    public function addMap(string $name, Map $map): CompiledDeploymentInterface
    {
        $this->maps[$name] = $map;

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

    public function foreachHook(callable $callback): CompiledDeploymentInterface
    {
        foreach ($this->hooks as $hook) {
            $callback($hook);
        }

        return $this;
    }

    /**
     * @return iterable<Pod>
     */
    private function listAllPods(): iterable
    {
        yield from $this->pods;

        foreach ($this->jobs as $job) {
            yield from $job->getPods();
        }
    }

    public function foreachVolume(callable $callback): CompiledDeploymentInterface
    {
        $volumesFetched = [];
        foreach ($this->volumes as $name => $volume) {
            if ($volume instanceof Volume || !isset($volumesFetched[$volume::class][$volume->getName()])) {
                $volumesFetched[$volume::class][$volume->getName()] = true;
                $callback($name, $volume, $this->prefix);
            }
        }

        foreach ($this->listAllPods() as $pod) {
            foreach ($pod as $container) {
                foreach ($container->getVolumes() as $name => $volume) {
                    if ($volume instanceof Volume || !isset($volumesFetched[$volume::class][$volume->getName()])) {
                        $volumesFetched[$volume::class][$volume->getName()] = true;
                        $callback($name, $volume, $this->prefix);
                    }
                }
            }
        }

        return $this;
    }

    public function foreachBuildable(callable $callback): CompiledDeploymentInterface
    {
        $processedBuildables = [];
        foreach ($this->listAllPods() as $pod) {
            foreach ($pod as $container) {
                $buildable = $container->getImage();
                $version = $container->getVersion();

                if (isset($processedBuildables[$buildable][$version])) {
                    continue;
                }

                $processedBuildables[$buildable][$version] = true;

                if ($this->hasBuildable($buildable, $version)) {
                    $callback(
                        $this->getBuildable($buildable, $version),
                        $this->prefix,
                    );
                }
            }
        }

        return $this;
    }

    public function foreachSecret(callable $callback): CompiledDeploymentInterface
    {
        foreach ($this->secrets as $secret) {
            $callback($secret, $this->prefix);
        }

        return $this;
    }

    public function foreachMap(callable $callback): CompiledDeploymentInterface
    {
        foreach ($this->maps as $map) {
            $callback($map, $this->prefix);
        }

        return $this;
    }

    private function callbackAboutPods(Pod|Job $object, Pod $pod, callable $callback): void
    {
        $buildables = [];
        $volumes = [];
        foreach ($pod as $container) {
            $imgName = $container->getImage();
            $imgVersion = $container->getVersion();

            if ($this->hasBuildable($imgName, $imgVersion)) {
                $buildables[$imgName][$imgVersion] = $this->getBuildable($imgName, $imgVersion);
                foreach ($container->getVolumes() as $name => $volume) {
                    if ($volume instanceof PopulatedVolumeInterface) {
                        $volumes[$container->getName() . '_' . $name] = $this->volumes[$name];
                    } else {
                        $volumes[$container->getName() . '_' . $name] = $volume;
                    }
                }
            }
        }

        $callback($object, $buildables, $volumes, $this->prefix);
    }

    public function foreachPod(callable $callback): CompiledDeploymentInterface
    {
        foreach ($this->pods as $pod) {
            $this->callbackAboutPods($pod, $pod, $callback);
        }

        return $this;
    }

    public function foreachJob(callable $callback): CompiledDeploymentInterface
    {
        foreach ($this->jobs as $job) {
            foreach ($job->getPods() as $pod) {
                $this->callbackAboutPods($job, $pod, $callback);
            }
        }

        return $this;
    }

    public function foreachService(callable $callback): CompiledDeploymentInterface
    {
        foreach ($this->services as $service) {
            $callback($service, $this->prefix);
        }

        return $this;
    }

    public function foreachIngress(callable $callback): CompiledDeploymentInterface
    {
        foreach ($this->ingresses as $ingress) {
            $callback($ingress, $this->prefix);
        }

        return $this;
    }

    public function compileDefaultsBags(string $name, callable $callback): CompiledDeploymentInterface
    {
        $callback($this->defaultsBag?->getBagFor($name) ?? new DefaultsBag());

        return $this;
    }

    public function withJobSettings(callable $callback): CompiledDeploymentInterface
    {
        $callback($this->version, $this->prefix, $this->projectName);

        return $this;
    }
}
