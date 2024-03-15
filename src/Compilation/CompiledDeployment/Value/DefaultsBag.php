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

namespace Teknoo\East\Paas\Compilation\CompiledDeployment\Value;

use DomainException;

use function array_key_exists;

/**
 * Bag to store a list a default value, generic, or per clusters, for a job, in some
 * dedicated place. Currently only for the storage provider (storage class in kubernetes),
 * default storage claiming size and oci registry config name (Pull Secret name in Kubernetes).
 * Defaults are set from the runner configuration or via the job unit configuration or in
 * the .paas.yaml file. It is able to override some entries for a cluster.
 *
 * The bag return a reference if a value is already defined in the first compilation step, the value
 * can be fetched during the deployment or the exposition.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class DefaultsBag
{
    /**
     * @var array<string, self>
     */
    private array $children = [];

    /**
     * @param array<string, string|null> $values
     */
    public function __construct(
        private ?self $parent = null,
        private array $values = [],
    ) {
    }

    public function set(string $name, ?string $value): self
    {
        $this->values[$name] = $value;

        return $this;
    }

    public function forCluster(string $name): self
    {
        return $this->children[$name] ??= new self($this);
    }

    public function getBagFor(string $name): self
    {
        if (isset($this->children[$name])) {
            return $this->children[$name];
        }

        return $this;
    }

    public function getReference(string $name): Reference
    {
        if (array_key_exists((string) $name, $this->values)) {
            return new Reference($name);
        }

        if (null !== $this->parent) {
            return $this->parent->getReference($name);
        }

        throw new DomainException("Error, there are no default value for `$name` in the current job");
    }

    public function resolve(Reference $name): ?string
    {
        if (array_key_exists((string) $name, $this->values)) {
            return $this->values[(string) $name];
        }

        if (null !== $this->parent) {
            return $this->parent->resolve($name);
        }

        throw new DomainException("Error, there are no default value for `$name` in the current job");
    }
}
