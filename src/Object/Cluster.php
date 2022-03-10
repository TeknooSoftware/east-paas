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

namespace Teknoo\East\Paas\Object;

use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Website\Object\ObjectInterface;
use Teknoo\East\Website\Object\ObjectTrait;
use Teknoo\East\Website\Object\TimestampableInterface;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface;
use Teknoo\East\Paas\Contracts\Object\FormMappingInterface;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

/**
 * Persisted object representing a cluster where perform a deployment.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Cluster implements
    ObjectInterface,
    TimestampableInterface,
    FormMappingInterface,
    NormalizableInterface
{
    use ObjectTrait;

    private ?Project $project = null;

    private ?string $name = null;

    private ?string $type = null;

    private ?string $address = null;

    private ?IdentityInterface $identity = null;

    private ?Environment $environment = null;

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

    public function injectDataInto($forms): FormMappingInterface
    {
        if (isset($forms['name'])) {
            $forms['name']->setData($this->getName());
        }

        if (isset($forms['type'])) {
            $forms['type']->setData($this->getType());
        }

        if (isset($forms['address'])) {
            $forms['address']->setData($this->getAddress());
        }

        if (isset($forms['identity'])) {
            $forms['identity']->setData($this->getIdentity());
        }

        if (isset($forms['environment'])) {
            $forms['environment']->setData($this->getEnvironment());
        }

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

        $environment->isEqualTo(
            $embeddedEnv,
            new Promise(
                fn () => $job->addCluster($this)
            )
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
