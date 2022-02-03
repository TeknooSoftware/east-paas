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

namespace Teknoo\East\Paas\Job;

use DomainException;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface as ClusterClientInterface;
use Teknoo\East\Paas\Cluster\Collection as ClusterCollection;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuilderInterface as ImageBuilder;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Contracts\Object\ImageRegistryInterface;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

use function is_array;
use function preg_replace_callback;
use function substr;

/**
 * Unit representing the current deployment execution' called a job.
 * This is a projection of the persisted object Job, dedicated to the execution.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class JobUnit implements JobUnitInterface
{
    /**
     * @param array{id: string, name: string} $projectResume
     * @param Cluster[] $clusters
     * @param array<string, string> $variables
     * @param array<string, mixed> $extra
     */
    public function __construct(
        private string $id,
        private array $projectResume,
        private Environment $environment,
        private ?string $baseNamespace,
        private SourceRepositoryInterface $sourceRepository,
        private ImageRegistryInterface $imagesRegistry,
        private array $clusters,
        private array $variables,
        private History $history,
        private array $extra = [],
    ) {
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
        } catch (Throwable $error) {
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
        } catch (Throwable $error) {
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
                        fn (Throwable $error) => throw $error,
                    )
                );
            }

            $promise->success(new ClusterCollection($selectedClients));
        } catch (Throwable $error) {
            $promise->fail($error);
        }

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
     * @param array{paas: array<string, mixed>} $values
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
                if (is_array($value)) {
                    $recursive($value, $recursive);

                    continue;
                }

                $value = preg_replace_callback(
                    $pattern,
                    /** @var callable(array<int|string, string>) $matches */
                    function (array $matches): string {
                        $key = substr($matches[1], 2, -1);
                        if (!isset($this->variables[$key])) {
                            throw new DomainException("$key is not available into variables pass to job");
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
        } catch (Throwable $error) {
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

    public function runWithExtra(callable $callback): JobUnitInterface
    {
        if (!empty($this->extra)) {
            $callback($this->extra);
        }

        return $this;
    }
}
