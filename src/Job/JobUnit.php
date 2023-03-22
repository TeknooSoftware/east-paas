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

namespace Teknoo\East\Paas\Job;

use DomainException;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\East\Paas\Contracts\Object\IdentityWithConfigNameInterface;
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

use function implode;
use function is_array;
use function preg_replace;
use function preg_replace_callback;
use function strlen;
use function substr;
use function strtolower;
use function trim;

/**
 * Unit representing the current deployment execution' called a job.
 * This is a projection of the persisted object Job, dedicated to the execution.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
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
        private readonly string $id,
        private readonly array $projectResume,
        private readonly Environment $environment,
        private readonly ?string $baseNamespace,
        private readonly ?string $prefix,
        private readonly SourceRepositoryInterface $sourceRepository,
        private readonly ImageRegistryInterface $imagesRegistry,
        private readonly array $clusters,
        private readonly array $variables,
        private readonly History $history,
        private readonly array $extra = [],
        private readonly bool $hierarchicalNamespaces = false,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getShortId(): string
    {
        $id = $this->getId();
        if (strlen($id) < 9) {
            return $id;
        }

        return substr(string: $id, offset: 0, length: 4) . '-' . substr(string: $id, offset: -4);
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
            /** @var Promise<ClusterClientInterface, mixed, mixed> $clusterPromise */
            $clusterPromise = new Promise(
                static function (ClusterClientInterface $client) use (&$selectedClients): void {
                    $selectedClients[] = $client;
                },
                static fn(Throwable $error) => throw $error,
            );

            foreach ($this->clusters as $cluster) {
                $cluster->selectCluster($clientsDirectory, $clusterPromise);
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
            'prefix' => $this->prefix,
            'hierarchical_namespaces' => $this->hierarchicalNamespaces,
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
        $parts = [];
        if (!empty($this->baseNamespace)) {
            $parts[] = $this->baseNamespace;
        }

        if (empty($parts) || true === $this->hierarchicalNamespaces) {
            $subPart = strtolower((string) ($values['paas']['namespace'] ?? $this->projectResume['name']));
            $subPart = trim($subPart);
            $subPart = preg_replace('#[^\pL\d]+#u', '', $subPart);

            $parts[] = $subPart;
        }

        $values['paas']['namespace'] = implode('-', $parts);
        $values['paas']['hierarchical-namespaces'] = $this->hierarchicalNamespaces;
        $values['paas']['prefix'] = $this->prefix;
    }

    /**
     * @param array{paas: array<string, mixed>} $values
     */
    private function updateDefaults(array &$values): void
    {
        if (
            !isset($values['defaults']['oci-registry-config-name'])
            && (($identity = $this->imagesRegistry->getIdentity()) instanceof IdentityWithConfigNameInterface)
            && !empty($identity->getConfigName())
        ) {
            $values['defaults']['oci-registry-config-name'] = $identity->getConfigName();
        }
    }

    /**
     * @param array<string, mixed> $values
     * @param PromiseInterface<array<string, mixed>, mixed> $promise
     */
    private function updateVariables(array &$values, PromiseInterface $promise): void
    {
        $pattern = '#((?:\$|R)\{[A-Za-z]\w*\})#iS';

        $updateClosure = function (&$values, callable $recursive) use ($pattern): void {
            foreach ($values as &$value) {
                if (is_array($value)) {
                    $recursive($value, $recursive);

                    continue;
                }

                $prefix = $this->prefix;
                if (!empty($prefix)) {
                    $prefix .= '-';
                }

                $value = preg_replace_callback(
                    $pattern,
                    /** @var callable(array<int|string, string>) $matches */
                    function (
                        array $matches,
                    ) use (
                        $prefix,
                    ): string {
                        $type = $matches[1][0];
                        $key = substr($matches[1], 2, -1);

                        if ('R' === $type) {
                            return $prefix . $key;
                        }

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
        $this->updateDefaults($values);

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
