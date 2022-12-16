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

use DateTimeInterface;
use RuntimeException;
use Stringable;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\East\Common\Contracts\Object\TimestampableInterface;
use Teknoo\East\Common\Contracts\Object\VisitableInterface;
use Teknoo\East\Common\Object\ObjectTrait;
use Teknoo\East\Paas\Contracts\Object\ImageRegistryInterface;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Object\Project\Draft;
use Teknoo\East\Paas\Object\Project\Executable;
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
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
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
    use AutomatedTrait {
        AutomatedTrait::updateStates insteadof ProxyTrait;
    }

    protected ?string $name = null;

    protected ?string $prefix = null;

    protected ?SourceRepositoryInterface $sourceRepository = null;

    protected ?ImageRegistryInterface $imagesRegistry = null;

    /**
     * @var array<int, Cluster>|iterable<Cluster>
     */
    protected iterable $clusters = [];

    /**
     * @var array<int, Job>|iterable<Job>
     */
    protected iterable $jobs = [];

    public function __construct(
        protected ?Account $account = null,
    ) {
        $this->initializeStateProxy();
        $this->updateStates();
    }

    /**
     * @return array<string>
     */
    public static function statesListDeclaration(): array
    {
        return [
            Draft::class,
            Executable::class,
        ];
    }

    /**
     * @return array<AssertionInterface>
     */
    protected function listAssertions(): array
    {
        return [
            (new Property(Executable::class))
                ->with('sourceRepository', new IsInstanceOf(SourceRepositoryInterface::class))
                ->with('imagesRegistry', new IsInstanceOf(ImageRegistryInterface::class))
                ->with('clusters', new CountsMore(0)),
            (new Callback(Draft::class))
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
            throw new RuntimeException('Error, the account has not been injected');
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

    private function getSourceRepository(): ?SourceRepositoryInterface
    {
        return $this->sourceRepository;
    }

    public function setSourceRepository(SourceRepositoryInterface $repository): Project
    {
        $this->sourceRepository = $repository;

        $this->updateStates();

        return $this;
    }

    private function getImagesRegistry(): ?ImageRegistryInterface
    {
        return $this->imagesRegistry;
    }

    public function setImagesRegistry(ImageRegistryInterface $repository): Project
    {
        $this->imagesRegistry = $repository;

        $this->updateStates();

        return $this;
    }

    /**
     * @return iterable<Cluster>
     */
    private function getClusters()
    {
        return $this->clusters;
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

    public function visit($visitors): VisitableInterface
    {
        if (isset($visitors['name'])) {
            $visitors['name']($this->getName());
        }

        if (isset($visitors['prefix'])) {
            $visitors['prefix']($this->getPrefix());
        }

        if (isset($visitors['sourceRepository'])) {
            $visitors['sourceRepository']($this->getSourceRepository());
        }

        if (isset($visitors['imagesRegistry'])) {
            $visitors['imagesRegistry']($this->getImagesRegistry());
        }

        if (isset($visitors['clusters'])) {
            $visitors['clusters']($this->getClusters());
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

    public function exportToMeData(EastNormalizerInterface $normalizer, array $context = []): NormalizableInterface
    {
        $normalizer->injectData([
            '@class' => self::class,
            'id' => $this->getId(),
            'name' => $this->getName(),
        ]);

        return $this;
    }
}
