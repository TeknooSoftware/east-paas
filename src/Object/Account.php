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

namespace Teknoo\East\Paas\Object;

use Stringable;
use Teknoo\East\Common\Contracts\Object\DeletableInterface;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\East\Common\Contracts\Object\TimestampableInterface;
use Teknoo\East\Common\Contracts\Object\VisitableInterface;
use Teknoo\East\Common\Object\ObjectTrait;
use Teknoo\East\Common\Object\User as BaseUser;
use Teknoo\East\Common\Object\VisitableTrait;
use Teknoo\East\Foundation\Normalizer\Object\AutoTrait;
use Teknoo\East\Foundation\Normalizer\Object\ClassGroup;
use Teknoo\East\Foundation\Normalizer\Object\Normalize;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\East\Paas\Contracts\Object\Account\AccountAwareInterface;
use Teknoo\East\Paas\Object\Account\Active;
use Teknoo\East\Paas\Object\Account\Inactive;
use Teknoo\States\Attributes\Assertion\Property;
use Teknoo\States\Attributes\StateClass;
use Teknoo\States\Automated\Assertion\Property\IsEmpty;
use Teknoo\States\Automated\Assertion\Property\IsNotEmpty;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyTrait;

/**
 * Persisted object representing an account on the platform, to create projects to deploy.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[StateClass(Active::class)]
#[StateClass(Inactive::class)]
#[Property(Active::class, ['name', IsNotEmpty::class])]
#[Property(Inactive::class, ['name', IsEmpty::class])]
#[ClassGroup('default', 'api', 'digest', 'crud')]
class Account implements
    IdentifiedObjectInterface,
    TimestampableInterface,
    AutomatedInterface,
    DeletableInterface,
    VisitableInterface,
    NormalizableInterface,
    Stringable
{
    use ObjectTrait;
    use ProxyTrait;
    use AutoTrait;
    use VisitableTrait {
        VisitableTrait::runVisit as realRunVisit;
    }
    use AutomatedTrait;

    #[Normalize(['default', 'api', 'digest', 'crud'])]
    protected ?string $id = null;

    #[Normalize(['default', 'api', 'digest', 'crud'])]
    protected ?string $name = null;

    #[Normalize(['default', 'admin'])]
    protected ?string $namespace = null;

    #[Normalize('admin')]
    protected ?string $prefixNamespace = null;

    /**
     * @var iterable<AccountQuota>|null
     */
    #[Normalize(['default', 'admin'])]
    protected ?iterable $quotas = null;

    /**
     * @var Project[]
     */
    protected iterable $projects = [];

    /**
     * @var BaseUser[]
     */
    #[Normalize('admin', loader: '@lazy')]
    protected ?iterable $users = [];

    public function __construct()
    {
        $this->initializeStateProxy();
        $this->updateStates();
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

    private function getPrefixNamespace(): ?string
    {
        return $this->prefixNamespace;
    }

    public function setPrefixNamespace(?string $prefixNamespace): Account
    {
        $this->prefixNamespace = $prefixNamespace;

        return $this;
    }

    /**
     * @return iterable<AccountQuota>|null
     */
    private function getQuotas(): ?iterable
    {
        return $this->quotas;
    }

    /**
     * @param iterable<AccountQuota>|null $quotas
     */
    public function setQuotas(?iterable $quotas): Account
    {
        $this->quotas = $quotas;

        return $this;
    }

    public function namespaceIsItDefined(callable $callback): Account
    {
        if ($namespace = $this->getNamespace()) {
            $callback($namespace, $this->getPrefixNamespace());
        }

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

    /**
     * @param array<string, callable> $visitors
     */
    private function runVisit(array &$visitors): void
    {
        if (isset($visitors['prefix_namespace'])) {
            $visitors['prefixNamespace'] = $visitors['prefix_namespace'];
            unset($visitors['prefix_namespace']);
        }

        $this->realRunVisit($visitors);
    }

    public function requireAccountNamespace(AccountAwareInterface $accountAware): Account
    {
        $accountAware->passAccountNamespace(
            $this,
            $this->name,
            $this->namespace,
            $this->prefixNamespace,
        );

        return $this;
    }
}
