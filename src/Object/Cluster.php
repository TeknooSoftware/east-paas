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

namespace Teknoo\East\Paas\Object;

use SensitiveParameter;
use Stringable;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\East\Common\Contracts\Object\TimestampableInterface;
use Teknoo\East\Common\Contracts\Object\VisitableInterface;
use Teknoo\East\Common\Object\ObjectTrait;
use Teknoo\East\Common\Object\VisitableTrait;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

/**
 * Persisted object representing a cluster where perform a deployment.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Cluster implements
    IdentifiedObjectInterface,
    TimestampableInterface,
    VisitableInterface,
    NormalizableInterface,
    Stringable
{
    use ObjectTrait;
    use VisitableTrait {
        VisitableTrait::runVisit as realRunVisit;
    }

    public function __construct(
        private ?Project $project = null,
        private ?string $name = null,
        private ?string $namespace = null,
        private bool $useHierarchicalNamespaces = false,
        private ?string $type = null,
        private ?string $address = null,
        private ?IdentityInterface $identity = null,
        private ?Environment $environment = null,
        /**
         * To not allow editing of the cluster
         */
        private bool $locked = false,
    ) {
    }

    public function setProject(Project $project): Cluster
    {
        $this->project = $project;

        return $this;
    }

    private function getName(): string
    {
        return (string) $this->name;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function setName(string $name): Cluster
    {
        $this->name = $name;

        return $this;
    }

    private function getNamespace(): string
    {
        return (string) $this->namespace;
    }

    public function setNamespace(string $namespace): Cluster
    {
        $this->namespace = $namespace;

        return $this;
    }

    private function hasHierarchicalNamespaces(): bool
    {
        return $this->useHierarchicalNamespaces;
    }

    public function useHierarchicalNamespaces(bool $hierarchicalNamespaces): Cluster
    {
        $this->useHierarchicalNamespaces = $hierarchicalNamespaces;

        return $this;
    }

    private function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): Cluster
    {
        $this->type = $type;

        return $this;
    }

    private function getAddress(): string
    {
        return (string) $this->address;
    }

    public function setAddress(string $address): Cluster
    {
        $this->address = $address;

        return $this;
    }

    private function getIdentity(): ?IdentityInterface
    {
        return $this->identity;
    }

    public function setIdentity(IdentityInterface $identity): Cluster
    {
        $this->identity = $identity;
        return $this;
    }

    private function getEnvironment(): ?Environment
    {
        return $this->environment;
    }

    public function setEnvironment(Environment $environment): Cluster
    {
        $this->environment = $environment;

        return $this;
    }

    public function isLocked(): ?bool
    {
        return $this->locked;
    }

    public function setLocked(bool $toLock): Cluster
    {
        $this->locked = $toLock;

        return $this;
    }

    public function tellMeYourEnvironment(callable $me): self
    {
        $me($this->environment);

        return $this;
    }

    public function prepareJobForEnvironment(Job $job, Environment $environment): self
    {
        $embeddedEnv = $this->getEnvironment();
        if (!$embeddedEnv instanceof Environment) {
            return $this;
        }

        /** @var Promise<Environment, mixed, mixed> $equalPromise */
        $equalPromise = new Promise(
            onSuccess: fn (): Job => $job->addCluster($this)
        );

        $environment->isEqualTo(
            $embeddedEnv,
            $equalPromise
        );

        return $this;
    }

    /**
     * @param array<string, callable> $visitors
     */
    private function runVisit(array &$visitors): void
    {
        if (isset($visitors['use_hierarchical_namespaces'])) {
            $visitors['useHierarchicalNamespaces'] = $visitors['use_hierarchical_namespaces'];
            unset($visitors['use_hierarchical_namespaces']);
        }

        $this->realRunVisit($visitors);
    }

    public function exportToMeData(EastNormalizerInterface $normalizer, array $context = []): NormalizableInterface
    {
        $normalizer->injectData([
            '@class' => self::class,
            'id' => $this->getId(),
            'name' => $this->getName(),
            'namespace' => $this->getNamespace(),
            'use_hierarchical_namespaces' => $this->hasHierarchicalNamespaces(),
            'type' => $this->getType(),
            'address' => $this->getAddress(),
            'identity' => $this->getIdentity(),
            'environment' => $this->getEnvironment(),
            'locked' => $this->isLocked(),
        ]);

        return $this;
    }

    /**
     * @param PromiseInterface<DriverInterface, mixed> $promise
     */
    public function selectCluster(
        Directory $clientsDirectory,
        CompiledDeploymentInterface $compiledDeployment,
        PromiseInterface $promise
    ): self {
        /** @var Promise<DefaultsBag, DefaultsBag, mixed> $defaultsBagPromise */
        $defaultsBagPromise = new Promise(
            fn (DefaultsBag $defaultsBag) => $defaultsBag,
            fn (#[SensitiveParameter] Throwable $error) => throw $error,
        );
        $defaultsBagPromise->setDefaultResult(new DefaultsBag());

        $compiledDeployment->compileDefaultsBags(
            $this->getName(),
            $defaultsBagPromise,
        );

        /** @var DefaultsBag $defaultsBag */
        $defaultsBag = $defaultsBagPromise->fetchResult();
        $clientsDirectory->require((string) $this->getType(), $defaultsBag, $this, $promise);

        return $this;
    }

    /**
     * @param PromiseInterface<DriverInterface, mixed> $promise
     */
    public function configureCluster(
        DriverInterface $client,
        DefaultsBag $resolver,
        PromiseInterface $promise,
    ): self {
        try {
            $promise->success(
                $client->configure(
                    url: $this->getAddress(),
                    identity: $this->getIdentity(),
                    defaultsBag: $resolver,
                    namespace: $this->getNamespace(),
                    useHierarchicalNamespaces: $this->hasHierarchicalNamespaces(),
                )
            );
        } catch (Throwable $error) {
            $promise->fail($error);
        }

        return $this;
    }
}
