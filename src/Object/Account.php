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
use Teknoo\East\Common\Contracts\Object\DeletableInterface;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\East\Common\Contracts\Object\TimestampableInterface;
use Teknoo\East\Common\Contracts\Object\VisitableInterface;
use Teknoo\East\Common\Object\ObjectTrait;
use Teknoo\East\Common\Object\User as BaseUser;
use Teknoo\East\Common\Object\VisitableTrait;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Foundation\Normalizer\Object\GroupsTrait;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\East\Paas\Contracts\Object\Account\AccountAwareInterface;
use Teknoo\East\Paas\Object\Account\Active;
use Teknoo\East\Paas\Object\Account\Inactive;
use Teknoo\East\Paas\Object\Traits\ExportConfigurationsTrait;
use Teknoo\States\Automated\Assertion\AssertionInterface;
use Teknoo\States\Automated\Assertion\Property;
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
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
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
    use GroupsTrait;
    use ExportConfigurationsTrait;
    use VisitableTrait {
        VisitableTrait::runVisit as realRunVisit;
    }
    use AutomatedTrait {
        AutomatedTrait::updateStates insteadof ProxyTrait;
    }

    protected ?string $name = null;

    protected ?string $namespace = null;

    protected ?string $prefixNamespace = null;

    /**
     * @var iterable<AccountQuota>|null
     */
    protected ?iterable $quotas = null;

    /**
     * @var Project[]
     */
    protected iterable $projects = [];

    /**
     * @var BaseUser[]
     */
    protected ?iterable $users = [];

    /**
     * @var array<string, string[]>
     */
    private static array $exportConfigurations = [
        '@class' => ['default', 'api', 'digest', 'crud'],
        'id' => ['default', 'api', 'digest', 'crud'],
        'name' => ['default', 'api', 'digest', 'crud'],
        'namespace' => ['default', 'admin'],
        'prefixNamespace' => ['admin'],
        'quota' => ['default', 'admin'],
        'users' => ['admin'],
    ];

    public function __construct()
    {
        $this->initializeStateProxy();
        $this->updateStates();
    }

    /**
     * @return array<string>
     */
    protected static function statesListDeclaration(): array
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

    public function exportToMeData(EastNormalizerInterface $normalizer, array $context = []): NormalizableInterface
    {
        $data = [
            '@class' => self::class,
            'id' => $this->getId(),
            'name' => $this->getName(),
            'namespace' => $this->getNamespace(),
            'prefixNamespace' => $this->getPrefixNamespace(),
            'quotas' => $this->getQuotas(),
            'users' => fn () => $this->getUsers(),
        ];

        $this->setGroupsConfiguration(self::$exportConfigurations);

        $normalizer->injectData(
            $this->filterExport(
                data: $data,
                groups: (array) ($context['groups'] ?? ['default']),
                lazyData: true,
            )
        );

        return $this;
    }
}
