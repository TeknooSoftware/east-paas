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

use Stringable;
use Teknoo\East\Common\Object\VisitableTrait;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\East\Common\Contracts\Object\TimestampableInterface;
use Teknoo\East\Common\Contracts\Object\VisitableInterface;
use Teknoo\East\Common\Object\ObjectTrait;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
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
    use VisitableTrait;

    private ?Project $project = null;

    private ?string $name = null;

    private ?string $type = null;

    private ?string $address = null;

    private ?IdentityInterface $identity = null;

    private ?Environment $environment = null;

    /**
     * To not allow editing of the cluster
     */
    private bool $locked = false;

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

    public function exportToMeData(EastNormalizerInterface $normalizer, array $context = []): NormalizableInterface
    {
        $normalizer->injectData([
            '@class' => self::class,
            'id' => $this->getId(),
            'name' => $this->getName(),
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
    public function selectCluster(Directory $clientsDirectory, PromiseInterface $promise): self
    {
        $clientsDirectory->require((string) $this->getType(), $this, $promise);

        return $this;
    }

    /**
     * @param PromiseInterface<DriverInterface, mixed> $promise
     */
    public function configureCluster(DriverInterface $client, PromiseInterface $promise): self
    {
        try {
            $promise->success(
                $client->configure(
                    $this->getAddress(),
                    $this->getIdentity()
                )
            );
        } catch (Throwable $error) {
            $promise->fail($error);
        }

        return $this;
    }
}
