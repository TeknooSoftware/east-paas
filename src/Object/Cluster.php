<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Object;

use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Website\Object\ObjectInterface;
use Teknoo\East\Website\Object\ObjectTrait;
use Teknoo\East\Website\Object\TimestampableInterface;
use Teknoo\East\Paas\Contracts\Cluster\ClientInterface;
use Teknoo\East\Paas\Contracts\Object\FormMappingInterface;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;

class Cluster implements
    ObjectInterface,
    TimestampableInterface,
    FormMappingInterface,
    NormalizableInterface
{
    use ObjectTrait;

    private ?Project $project = null;

    private ?string $name = null;

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

    public function prepareJobForEnvironment(Job $job, Environment $environment): self
    {
        $embeddedEnv = $this->getEnvironment();
        if (!$embeddedEnv instanceof Environment) {
            return $this;
        }

        $environment->isEqualTo($embeddedEnv, new Promise(function () use ($job) {
            $job->addCluster($this);
        }));

        return $this;
    }

    public function exportToMeData(EastNormalizerInterface $normalizer, array $context = []): NormalizableInterface
    {
        $normalizer->injectData([
            '@class' => self::class,
            'id' => $this->getId(),
            'name' => $this->getName(),
            'address' => $this->getAddress(),
            'identity' => $this->getIdentity(),
            'environment' => $this->getEnvironment(),
        ]);

        return $this;
    }

    public function configureCluster(ClientInterface $client, PromiseInterface $promise): self
    {
        try {
            $promise->success(
                $client->configure(
                    $this->getAddress(),
                    $this->getIdentity()
                )
            );
        } catch (\Throwable $error) {
            $promise->fail($error);
        }

        return $this;
    }
}
