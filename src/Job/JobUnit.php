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

namespace Teknoo\East\Paas\Job;

use DomainException;
use SensitiveParameter;
use SplQueue;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\East\Paas\Cluster\Collection as ClusterCollection;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Compilation\Compiler\Quota\Factory as QuotaFactory;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface as ClusterClientInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuilderInterface as ImageBuilder;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\Quota\AvailabilityInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Object\IdentityWithConfigNameInterface;
use Teknoo\East\Paas\Contracts\Object\ImageRegistryInterface;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Object\AccountQuota;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Object\Job;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

use function array_merge;
use function implode;
use function is_array;
use function is_string;
use function preg_replace;
use function preg_replace_callback;
use function strlen;
use function strtolower;
use function substr;
use function trim;

/**
 * Unit representing the current deployment execution' called a job.
 * This is a projection of the persisted object Job, dedicated to the execution.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class JobUnit implements JobUnitInterface
{
    /**
     * @param array{id: string, name: string} $projectResume
     * @param Cluster[] $clusters
     * @param array<string, string> $variables
     * @param array<string, mixed> $extra
     * @param array<string, mixed> $defaults
     * @param AccountQuota[] $quotas
     */
    public function __construct(
        private readonly string $id,
        private readonly array $projectResume,
        private readonly Environment $environment,
        private readonly ?string $prefix,
        private readonly SourceRepositoryInterface $sourceRepository,
        private readonly ImageRegistryInterface $imagesRegistry,
        private readonly array $clusters,
        private readonly array $variables,
        private readonly History $history,
        private readonly array $extra = [],
        private readonly array $defaults = [],
        private readonly iterable $quotas = []
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

    private static function normalizeString(string $text, string $replace): string
    {
        return strtolower(trim((string) preg_replace('#[^A-Za-z0-9-]+#', $replace, $text)));
    }

    public function getEnvironmentTag(): string
    {
        return self::normalizeString((string) $this->environment, '-');
    }

    public function getProjectNormalizedName(): string
    {
        return self::normalizeString((string) ($this->projectResume['name'] ?? ''), '');
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
        PromiseInterface $promise,
        CompiledDeploymentInterface $compiledDeployment,
    ): JobUnitInterface {
        try {
            $selectedClients = new SplQueue();
            /** @var Promise<ClusterClientInterface, mixed, mixed> $clusterPromise */
            $clusterPromise = new Promise(
                onSuccess: static function (ClusterClientInterface $client) use ($selectedClients): void {
                    $selectedClients[] = $client;
                },
                onFail: static fn(#[SensitiveParameter] Throwable $error) => throw $error,
            );

            foreach ($this->clusters as $cluster) {
                $cluster->selectCluster($clientsDirectory, $compiledDeployment, $clusterPromise);
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
            'prefix' => $this->prefix,
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
    private function updateConfig(#[SensitiveParameter] array &$values): void
    {
        $values['paas']['prefix'] = $this->prefix;
    }

    /**
     * @param array{paas: array<string, mixed>} $values
     */
    private function updateDefaults(#[SensitiveParameter] array &$values): void
    {
        $recursiveMerge = function (callable $recursiveMerge, array $array1, array $array2): array {
            $final = $array1;
            foreach ($array2 as $key => &$value) {
                if (is_array($value) && isset($final[$key]) && is_array($final[$key])) {
                    $final[$key] = $recursiveMerge($recursiveMerge, $final[$key], $value);
                } else {
                    $final[$key] = $value;
                }
            }

            return $final;
        };

        $values['defaults'] = $recursiveMerge(
            $recursiveMerge,
            $this->defaults,
            $values['defaults'] ?? [],
        );

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
    private function updateVariables(
        #[SensitiveParameter] array &$values,
        PromiseInterface $promise,
    ): void {
        $pattern = '#((?:\$|R)\{[A-Za-z]\w*\})#iS';

        $prefix = $this->prefix;
        if (!empty($prefix)) {
            $prefix .= '-';
        }

        $variables = $this->variables;
        $variables['JOB_ENV_TAG'] = $this->getEnvironmentTag();
        $variables['JOB_PROJECT_NAME'] = $this->getProjectNormalizedName();

        /** @var callable(array<int|string, string>) $matches */
        $replaceCallable = static function (
            array $matches,
        ) use (
            &$prefix,
            &$variables,
        ): string {
            $type = $matches[1][0];
            $key = substr($matches[1], 2, -1);

            if ('R' === $type) {
                return $prefix . $key;
            }

            if (!isset($variables[$key])) {
                throw new DomainException("$key is not available into variables pass to job");
            }

            return $variables[$key];
        };

        $updateClosure = static function (&$values, callable $recursive) use (&$replaceCallable, &$pattern): void {
            foreach ($values as $key => &$value) {
                if (is_array($value)) {
                    $recursive($value, $recursive);

                    continue;
                }

                if (is_string($value)) {
                    $value = preg_replace_callback(
                        $pattern,
                        $replaceCallable,
                        $value,
                    );
                }

                if (!is_string($key)) {
                    continue;
                }

                $finalKey = preg_replace_callback(
                    $pattern,
                    $replaceCallable,
                    $key,
                );

                if ($key !== $finalKey) {
                    $values[$finalKey] = $value;
                    unset($values[$key]);
                }
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
        #[SensitiveParameter] array $values,
        PromiseInterface $promise
    ): JobUnitInterface {
        $this->updateConfig($values);
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

    /**
     * @param PromiseInterface<array<string, AvailabilityInterface>, mixed> $promise
     */
    public function prepareQuotas(QuotaFactory $factory, PromiseInterface $promise): JobUnitInterface
    {
        $final = [];
        foreach ($this->quotas as $quota) {
            $final[$quota->type] = $factory->create(
                category: $quota->category,
                type: $quota->type,
                capacity: $quota->capacity,
                requires: $quota->getRequires(),
                isSoft: false,
            );
        }

        $promise->success($final);

        return $this;
    }
}
