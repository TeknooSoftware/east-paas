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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Object;

use Teknoo\East\Website\Object\DeletableInterface;
use Teknoo\East\Website\Object\ObjectInterface;
use Teknoo\East\Website\Object\ObjectTrait;
use Teknoo\East\Website\Object\TimestampableInterface;
use Teknoo\East\Website\Object\User as BaseUser;
use Teknoo\East\Paas\Contracts\Object\FormMappingInterface;
use Teknoo\East\Paas\Object\Account\Active;
use Teknoo\East\Paas\Object\Account\Inactive;
use Teknoo\States\Automated\Assertion\AssertionInterface;
use Teknoo\States\Automated\Assertion\Property;
use Teknoo\States\Automated\Assertion\Property\IsEmpty;
use Teknoo\States\Automated\Assertion\Property\IsNotEmpty;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyInterface;
use Teknoo\States\Proxy\ProxyTrait;

/**
 * Persisted object representing an account on the platform, to create projects to deploy.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Account implements
    ObjectInterface,
    TimestampableInterface,
    AutomatedInterface,
    DeletableInterface,
    FormMappingInterface
{
    use ObjectTrait;
    use ProxyTrait;
    use AutomatedTrait {
        AutomatedTrait::updateStates insteadof ProxyTrait;
    }

    protected ?string $name = null;

    protected ?string $namespace = null;

    /**
     * @var Project[]
     */
    protected iterable $projects = [];

    /**
     * @var BaseUser[]
     */
    protected ?iterable $users = [];

    public function __construct()
    {
        $this->initializeStateProxy();
        $this->updateStates();
    }

    /**
     * @return array<string>
     */
    public static function statesListDeclaration(): array
    {
        return [
            Active::class,
            Inactive::class,
        ];
    }

    /**
     * @return array<AssertionInterface>
     */
    protected function listAssertions(): array
    {
        return [
            (new Property(Active::class))
                ->with('name', new IsNotEmpty()),
            (new Property(Inactive::class))
                ->with('name', new IsEmpty()),
        ];
    }

    private function getName(): string
    {
        return (string) $this->name;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function setName(string $name): Account
    {
        $this->name = $name;

        $this->updateStates();

        return $this;
    }

    private function getNamespace(): ?string
    {
        return $this->namespace;
    }

    public function setNamespace(?string $namespace): Account
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * @param iterable<Project> $projects
     */
    public function setProjects(iterable $projects): Account
    {
        $this->projects = $projects;

        return $this;
    }

    /**
     * @return BaseUser[]
     */
    private function getUsers(): ?iterable
    {
        return $this->users;
    }

    /**
     * @param iterable<BaseUser> $users
     */
    public function setUsers(?iterable $users): Account
    {
        $this->users = $users;

        return $this;
    }

    public function injectDataInto($forms): FormMappingInterface
    {
        if (isset($forms['name'])) {
            $forms['name']->setData($this->getName());
        }

        if (isset($forms['namespace'])) {
            $forms['namespace']->setData($this->getNamespace());
        }

        if (isset($forms['users'])) {
            $forms['users']->setData($this->getUsers());
        }

        return $this;
    }
}
