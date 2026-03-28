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

use DateTimeInterface;
use Stringable;
use Teknoo\East\Common\Object\VisitableTrait;
use Teknoo\East\Foundation\Normalizer\Object\AutoTrait;
use Teknoo\East\Foundation\Normalizer\Object\ClassGroup;
use Teknoo\East\Foundation\Normalizer\Object\Normalize;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\East\Common\Contracts\Object\TimestampableInterface;
use Teknoo\East\Common\Contracts\Object\VisitableInterface;
use Teknoo\East\Common\Object\ObjectTrait;
use Teknoo\East\Paas\Contracts\Object\ImageRegistryInterface;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Object\Exception\MissingAccountException;
use Teknoo\East\Paas\Object\Project\Draft;
use Teknoo\East\Paas\Object\Project\Executable;
use Teknoo\States\Attributes\StateClass;
use Teknoo\States\Automated\Assertion\AssertionInterface;
use Teknoo\States\Automated\Assertion\Property;
use Teknoo\States\Automated\Assertion\Callback;
use Teknoo\States\Automated\Assertion\Property\IsInstanceOf;
use Teknoo\States\Automated\Assertion\Property\CountsMore;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyTrait;

use const PHP_INT_MAX;

/**
 * Persisted object representing a project, in an Account, to deploy on clusters from a source repository.
 *
 * @method Project prepareJob(Job $job, DateTimeInterface $date, Environment $environment)
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[StateClass(Draft::class)]
#[StateClass(Executable::class)]
#[ClassGroup('default', 'api', 'digest', 'crud')]
class Project implements
    IdentifiedObjectInterface,
    AutomatedInterface,
    TimestampableInterface,
    VisitableInterface,
    NormalizableInterface,
    Stringable
{
    use ObjectTrait;
    use ProxyTrait;
    use AutoTrait;
    use VisitableTrait;
    use AutomatedTrait;

    #[Normalize(['default', 'api', 'digest', 'crud'])]
    protected ?string $id = null;

    #[Normalize(['default', 'api', 'digest', 'crud'])]
    protected ?string $name = null;

    #[Normalize('crud')]
    protected ?string $prefix = null;

    #[Normalize('crud', loader: '@lazy')]
    protected ?SourceRepositoryInterface $sourceRepository = null;

    #[Normalize('crud', loader: '@lazy')]
    protected ?ImageRegistryInterface $imagesRegistry = null;

    /**
     * @var array<int, Cluster>|iterable<Cluster>
     */
    #[Normalize('crud', loader: '@lazy')]
    protected iterable $clusters = [];

    /**
     * @var array<int, Job>|iterable<Job>
     */
    protected iterable $jobs = [];

    public function __construct(
        #[Normalize('crud')]
        protected ?Account $account = null,
    ) {
        $this->initializeStateProxy();
        $this->updateStates();
    }

    /**
     * @return array<AssertionInterface>
     */
    protected function listAssertions(): array
    {
        return [
            new Property(Executable::class)
                ->with('sourceRepository', new IsInstanceOf(SourceRepositoryInterface::class))
                ->with('imagesRegistry', new IsInstanceOf(ImageRegistryInterface::class))
                ->with('clusters', new CountsMore(0)),
            new Callback(Draft::class)
                ->call(static function (Project $project, AssertionInterface $assertion): void {
                    $project->isNotInState([Executable::class], static function () use ($assertion): void {
                        $assertion->isValid();
                    });
                }),
        ];
    }

    public function getAccount(): Account
    {
        if (!$this->account instanceof Account) {
            throw new MissingAccountException('Error, the account has not been injected');
        }

        return $this->account;
    }

    private function getName(): string
    {
        return (string) $this->name;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function setName(string $name): Project
    {
        $this->name = $name;
        return $this;
    }

    private function getPrefix(): string
    {
        return (string) $this->prefix;
    }

    public function setPrefix(string $prefix): Project
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function setSourceRepository(SourceRepositoryInterface $repository): Project
    {
        $this->sourceRepository = $repository;

        $this->updateStates();

        return $this;
    }

    public function setImagesRegistry(ImageRegistryInterface $repository): Project
    {
        $this->imagesRegistry = $repository;

        $this->updateStates();

        return $this;
    }

    /**
     * @param Cluster[] $clusters
     */
    public function setClusters(iterable $clusters): Project
    {
        $this->clusters = $clusters;

        $this->updateStates();

        $this->updateClusters();

        return $this;
    }

    public function updateClusters(): Project
    {
        if (!empty($this->clusters)) {
            foreach ($this->clusters as $cluster) {
                $cluster->setProject($this);
            }
        }

        return $this;
    }

    public function refuseExecution(Job $job, string $error, DateTimeInterface $date): self
    {
        $job->addToHistory(
            message: $error,
            date: $date,
            isFinal: true,
            serialNumber: PHP_INT_MAX,
        );

        return $this;
    }
}
