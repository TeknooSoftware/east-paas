<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
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

namespace Teknoo\East\Paas\Job;

use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Contracts\Cluster\ClientInterface as ClusterClientInterface;
use Teknoo\East\Paas\Cluster\Collection as ClusterCollection;
use Teknoo\East\Paas\Contracts\Container\BuilderInterface as ImageBuilder;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Contracts\Object\ImageRegistryInterface;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class JobUnit implements JobUnitInterface
{
    private string $id;

    /**
     * @var array<string, mixed>
     */
    private array $projectResume;

    private Environment $environment;

    private ?string $baseNamespace = null;

    private SourceRepositoryInterface $sourceRepository;

    private ImageRegistryInterface $imagesRegistry;

    /**
     * @var Cluster[]
     */
    private array $clusters;

    private History $history;

    /**
     * @var array<string, mixed>
     */
    private array $variables;

    /**
     * @param array<string, mixed> $projectResume
     * @param Cluster[] $clusters
     * @param array<string, mixed> $variables
     */
    public function __construct(
        string $id,
        array $projectResume,
        Environment $environment,
        ?string $baseNamespace,
        SourceRepositoryInterface $sourceRepository,
        ImageRegistryInterface $imagesRegistry,
        array $clusters,
        array $variables,
        History $history
    ) {
        $this->id = $id;
        $this->projectResume = $projectResume;
        $this->environment = $environment;
        $this->baseNamespace = $baseNamespace;
        $this->sourceRepository = $sourceRepository;
        $this->imagesRegistry = $imagesRegistry;
        $this->clusters = $clusters;
        $this->variables = $variables;
        $this->history = $history;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function configureCloningAgent(
        CloningAgentInterface $agent,
        JobWorkspaceInterface $workspace,
        PromiseInterface $promise
    ): JobUnitInterface {
        try {
            $agent = $agent->configure(
                $this->sourceRepository,
                $workspace
            );

            $promise->success($agent);
        } catch (\Throwable $error) {
            $promise->fail($error);
        }

        return $this;
    }

    public function configureImageBuilder(
        ImageBuilder $builder,
        PromiseInterface $promise
    ): JobUnitInterface {
        try {
            $builder = $builder->configure(
                $this->projectResume['id'],
                $this->imagesRegistry->getApiUrl(),
                $this->imagesRegistry->getIdentity()
            );

            $promise->success($builder);
        } catch (\Throwable $error) {
            $promise->fail($error);
        }

        return $this;
    }

    public function configureCluster(
        Directory $clientsDirectory,
        PromiseInterface $promise
    ): JobUnitInterface {
        try {
            $selectedClients = [];
            foreach ($this->clusters as $cluster) {
                $cluster->selectCluster(
                    $clientsDirectory,
                    new Promise(
                        static function (ClusterClientInterface $client) use (&$selectedClients) {
                            $selectedClients[] = $client;
                        },
                        static function (\Throwable $error) {
                            throw $error;
                        }
                    )
                );
            }

            $promise->success(new ClusterCollection($selectedClients));
        } catch (\Throwable $error) {
            $promise->fail($error);
        }

        return $this;
    }

    public function prepareUrl(string $url, PromiseInterface $promise): JobUnitInterface
    {
        $url = \str_replace(
            ['{projectId}','{envName}','{jobId}'],
            [$this->projectResume['id'], $this->environment->getName(), $this->getId()],
            $url
        );

        $promise->success($url);

        return $this;
    }

    public function exportToMeData(EastNormalizerInterface $normalizer, array $context = []): NormalizableInterface
    {
        $normalizer->injectData([
            '@class' => Job::class,
            'id' => $this->getId(),
            'project' => $this->projectResume,
            'base_namespace' => $this->baseNamespace,
            'environment' => $this->environment,
            'source_repository' => $this->sourceRepository,
            'images_repository' => $this->imagesRegistry,
            'clusters' => $this->clusters,
            'variables' => $this->variables,
            'history' => $this->history,
        ]);

        return $this;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function updateNamespace(array &$values): void
    {
        $namespace = ($values['paas']['namespace'] ?? $this->projectResume['name']);

        if (!empty($this->baseNamespace)) {
            $namespace = $this->baseNamespace . '/' . $namespace;
        }

        if (!empty($namespace)) {
            $values['paas']['namespace'] = $namespace;
        }
    }

    /**
     * @param array<string, mixed> $values
     */
    private function updateVariables(array &$values, PromiseInterface $promise): void
    {
        $pattern = '#(\$\{[A-Za-z][A-Za-z0-9_]*\})#iS';

        $updateClosure = function (&$values, callable $recursive) use ($pattern) {
            foreach ($values as $name => &$value) {
                if (\is_array($value)) {
                    $recursive($value, $recursive);

                    continue;
                }

                $value = \preg_replace_callback(
                    $pattern,
                    function ($matches) {
                        $key = \substr($matches[1], 2, -1);
                        if (!isset($this->variables[$key])) {
                            throw new \DomainException("$key is not available into variables pass to job");
                        }

                        return $this->variables[$key];
                    },
                    (string) $value
                );
            }
        };

        try {
            $updateClosure($values, $updateClosure);
            $promise->success($values);
        } catch (\Throwable $error) {
            $promise->fail($error);
        }
    }

    public function updateVariablesIn(
        array $values,
        PromiseInterface $promise
    ): JobUnitInterface {
        $this->updateNamespace($values);

        $this->updateVariables($values, $promise);

        return $this;
    }
}
