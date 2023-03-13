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
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\East\Common\Object\ObjectTrait;
use Teknoo\East\Common\Contracts\Object\TimestampableInterface;
use Teknoo\East\Paas\Contracts\Object\ImageRegistryInterface;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Object\Job\Executing;
use Teknoo\East\Paas\Object\Job\Pending;
use Teknoo\East\Paas\Object\Job\Terminated;
use Teknoo\East\Paas\Object\Job\Validating;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\States\Automated\Assertion\AssertionInterface;
use Teknoo\States\Automated\Assertion\Callback;
use Teknoo\States\Automated\Assertion\Property;
use Teknoo\States\Automated\Assertion\Property\IsInstanceOf;
use Teknoo\States\Automated\Assertion\Property\CountsMore;
use Teknoo\States\Automated\Assertion\Property\IsEmpty;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyInterface;
use Teknoo\States\Proxy\ProxyTrait;

use function is_callable;

/**
 * Persisted object representing a deployment of a project on a cluster from a source repository.
 *
 * @method Job configureCloningAgent(CloningAgentInterface $agent, JobWorkspaceInterface $workspace)
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/states Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Job implements
    IdentifiedObjectInterface,
    AutomatedInterface,
    TimestampableInterface,
    NormalizableInterface
{
    use ObjectTrait;
    use ProxyTrait;
    use AutomatedTrait {
        AutomatedTrait::updateStates insteadof ProxyTrait;
    }

    protected ?Project $project = null;

    protected ?Environment $environment = null;

    protected ?string $baseNamespace = null;

    protected bool $hierarchicalNamespaces = false;

    protected ?string $prefix = null;

    protected ?SourceRepositoryInterface $sourceRepository = null;

    protected ?ImageRegistryInterface $imagesRegistry = null;

    /**
     * @var array<int, Cluster>
     */
    protected iterable $clusters = [];

    protected ?History $history = null;

    /**
     * @var array<string, mixed>
     */
    private array $extra = [];

    public function __construct()
    {
        $this->initializeStateProxy();
        $this->updateStates();
    }

    /**
     * @return array<AssertionInterface>
     */
    protected function listAssertions(): array
    {
        return [
            (new Property(Terminated::class))
                ->with('history', new IsInstanceOf(History::class))
                ->with(
                    'history',
                    new Property\Callback(
                        static function (History $history, Property\Callback $assertion): void {
                            if ($history->isFinal()) {
                                $assertion->isValid($history);
                            }
                        }
                    ),
                ),

            (new Property(Executing::class))
                ->with('project', new IsInstanceOf(Project::class))
                ->with('environment', new IsInstanceOf(Environment::class))
                ->with('sourceRepository', new IsInstanceOf(SourceRepositoryInterface::class))
                ->with('imagesRegistry', new IsInstanceOf(ImageRegistryInterface::class))
                ->with('clusters', new CountsMore(0))
                ->with('history', new IsInstanceOf(History::class))
                ->with(
                    'history',
                    new Property\Callback(
                        static function (History $history, Property\Callback $assertion): void {
                            if (!$history->isFinal()) {
                                $assertion->isValid($history);
                            }
                        }
                    )
                ),

            (new Property(Validating::class))
                ->with('project', new IsInstanceOf(Project::class))
                ->with('environment', new IsInstanceOf(Environment::class))
                ->with('sourceRepository', new IsInstanceOf(SourceRepositoryInterface::class))
                ->with('imagesRegistry', new IsInstanceOf(ImageRegistryInterface::class))
                ->with('clusters', new CountsMore(0))
                ->with('history', new IsEmpty()),

            (new Callback(Pending::class))
                ->call(static function (Job $job, AssertionInterface $assertion): void {
                    $job->isNotInState(
                        [Validating::class, Executing::class, Terminated::class],
                        static function ($statesList) use ($assertion): void {
                            if (empty($statesList)) {
                                $assertion->isValid();
                            }
                        }
                    );
                }),
        ];
    }

    /**
     * @return array<string>
     */
    public static function statesListDeclaration(): array
    {
        return [
            Executing::class,
            Pending::class,
            Terminated::class,
            Validating::class,
        ];
    }

    public function setBaseNamespace(?string $baseNamespace): Job
    {
        $this->baseNamespace = $baseNamespace;

        return $this;
    }

    public function setPrefix(?string $prefix): Job
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function useHierarchicalNamespaces(bool $hierarchicalNamespaces): Job
    {
        $this->hierarchicalNamespaces = $hierarchicalNamespaces;

        return $this;
    }

    public function addFromHistory(History $history, ?callable $callback = null): Job
    {
        $history = $history->clone($this->history);

        $this->setHistory($history);

        if (is_callable($callback)) {
            $callback($history);
        }

        return $this;
    }

    /**
     * @param array<string, mixed> $extra
     */
    public function addToHistory(
        string $message,
        DateTimeInterface $date,
        bool $isFinal = false,
        array $extra = [],
        int $serialNumber = 0,
    ): Job {
        $this->setHistory(
            new History(
                previous: $this->history,
                message: $message,
                date: $date,
                isFinal: $isFinal,
                extra: $extra,
                serialNumber: $serialNumber,
            )
        );

        return $this;
    }

    public function setHistory(?History $history): Job
    {
        $this->history = $history;

        $this->updateStates();

        return $this;
    }

    public function getHistory(): ?History
    {
        return $this->history;
    }

    public function exportToMeData(EastNormalizerInterface $normalizer, array $context = []): NormalizableInterface
    {
        $normalizer->injectData([
            '@class' => self::class,
            'id' => $this->getId(),
            'project' => $this->project,
            'base_namespace' => $this->baseNamespace,
            'prefix' => $this->prefix,
            'hierarchical_namespaces' => $this->hierarchicalNamespaces,
            'environment' => $this->environment,
            'source_repository' => $this->sourceRepository,
            'images_repository' => $this->imagesRegistry,
            'clusters' => $this->clusters,
            'history' => $this->history,
            'extra' => $this->extra,
        ]);

        return $this;
    }

    public function setProject(Project $project): Job
    {
        return $this->settingProject($project);
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setEnvironment(Environment $environment): Job
    {
        return $this->settingEnvironment($environment);
    }

    public function setSourceRepository(SourceRepositoryInterface $repository): Job
    {
        return $this->settingSourceRepository($repository);
    }

    public function setImagesRegistry(ImageRegistryInterface $repository): Job
    {
        return $this->settingImagesRegistry($repository);
    }

    public function addCluster(Cluster $cluster): Job
    {
        return $this->addingCluster($cluster);
    }

    /**
     * @param array<int, Cluster>|iterable<int, Cluster> $clusters
     */
    public function setClusters(iterable $clusters): Job
    {
        $this->clusters = $clusters;

        $this->updateStates();

        return $this;
    }

    /**
     * @param array<string, mixed> $extra
     */
    public function setExtra(array $extra): Job
    {
        $this->extra += $extra;

        return $this;
    }
}
