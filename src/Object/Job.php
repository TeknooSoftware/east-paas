<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Object;

use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\East\Website\Object\ObjectInterface;
use Teknoo\East\Website\Object\ObjectTrait;
use Teknoo\East\Website\Object\TimestampableInterface;
use Teknoo\East\Paas\Contracts\Object\ImagesRepositoryInterface;
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

/**
 * @method Job configureCloningAgent(CloningAgentInterface $agent, JobWorkspaceInterface $workspace)
 */
class Job implements
    ObjectInterface,
    ProxyInterface,
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

    protected ?SourceRepositoryInterface $sourceRepository = null;

    protected ?ImagesRepositoryInterface $imagesRepository = null;

    /**
     * @var array<int, Cluster>
     */
    protected iterable $clusters = [];

    protected ?History $history = null;

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
                ->with('history', new Property\Callback(function (History $history, Property\Callback $assertion) {
                    if ($history->isFinal()) {
                        $assertion->isValid($history);
                    }
                })),

            (new Property(Executing::class))
                ->with('project', new IsInstanceOf(Project::class))
                ->with('environment', new IsInstanceOf(Environment::class))
                ->with('sourceRepository', new IsInstanceOf(SourceRepositoryInterface::class))
                ->with('imagesRepository', new IsInstanceOf(ImagesRepositoryInterface::class))
                ->with('clusters', new CountsMore(0))
                ->with('history', new IsInstanceOf(History::class))
                ->with('history', new Property\Callback(function (History $history, Property\Callback $assertion) {
                    if (!$history->isFinal()) {
                        $assertion->isValid($history);
                    }
                })),

            (new Property(Validating::class))
                ->with('project', new IsInstanceOf(Project::class))
                ->with('environment', new IsInstanceOf(Environment::class))
                ->with('sourceRepository', new IsInstanceOf(SourceRepositoryInterface::class))
                ->with('imagesRepository', new IsInstanceOf(ImagesRepositoryInterface::class))
                ->with('clusters', new CountsMore(0))
                ->with('history', new IsEmpty()),

            (new Callback(Pending::class))
                ->call(function (Job $job, AssertionInterface $assertion) {
                    $job->isNotInState(
                        [Validating::class, Executing::class, Terminated::class],
                        function ($statesList) use ($assertion) {
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

    public function addFromHistory(History $history, ?callable $callback = null): Job
    {
        $history = $history->clone($this->history);

        $this->setHistory($history);

        if (\is_callable($callback)) {
            $callback($history);
        }

        return $this;
    }

    /**
     * @param array<string, mixed> $extra
     */
    public function addToHistory(
        string $message,
        \DateTimeInterface $date,
        bool $isFinal = false,
        array $extra = []
    ): Job {
        $this->setHistory(new History($this->history, $message, $date, $isFinal, $extra));

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
            'environment' => $this->environment,
            'source_repository' => $this->sourceRepository,
            'images_repository' => $this->imagesRepository,
            'clusters' => $this->clusters,
            'history' => $this->history,
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

    public function setImagesRepository(ImagesRepositoryInterface $repository): Job
    {
        return $this->settingImagesRepository($repository);
    }

    public function addCluster(Cluster $cluster): Job
    {
        return $this->addingCluster($cluster);
    }

    /**
     * @param array<int, Cluster>|iterable<Cluster> $clusters
     */
    public function setClusters(iterable $clusters): Job
    {
        $this->clusters = $clusters;

        $this->updateStates();

        return $this;
    }
}
