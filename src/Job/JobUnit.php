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
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
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
use Teknoo\East\Paas\Contracts\Cluster\ClientInterface as ClusterClientInterface;
use Teknoo\East\Paas\Cluster\Collection as ClusterCollection;
use Teknoo\East\Paas\Contracts\Container\BuilderInterface as ImageBuilder;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Contracts\Object\ImagesRepositoryInterface;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;

class JobUnit implements JobUnitInterface
{
    private string $id;

    /**
     * @var array<string, mixed>
     */
    private array $projectResume;

    private Environment $environment;

    private SourceRepositoryInterface $sourceRepository;

    private ImagesRepositoryInterface $imagesRepository;

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
        SourceRepositoryInterface $sourceRepository,
        ImagesRepositoryInterface $imagesRepository,
        array $clusters,
        array $variables,
        History $history
    ) {
        $this->id = $id;
        $this->projectResume = $projectResume;
        $this->environment = $environment;
        $this->sourceRepository = $sourceRepository;
        $this->imagesRepository = $imagesRepository;
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
                $this->imagesRepository->getApiUrl(),
                $this->imagesRepository->getIdentity()
            );

            $promise->success($builder);
        } catch (\Throwable $error) {
            $promise->fail($error);
        }

        return $this;
    }

    public function configureCluster(
        ClusterClientInterface $client,
        PromiseInterface $promise
    ): JobUnitInterface {
        try {
            $clients = [];
            foreach ($this->clusters as $cluster) {
                $cluster->configureCluster(
                    $client,
                    new Promise(
                        static function (ClusterClientInterface $client) use (&$clients) {
                            $clients[] = $client;
                        },
                        static function (\Throwable $error) {
                            throw $error;
                        }
                    )
                );
            }

            $promise->success(new ClusterCollection($clients));
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
            'environment' => $this->environment,
            'source_repository' => $this->sourceRepository,
            'images_repository' => $this->imagesRepository,
            'clusters' => $this->clusters,
            'variables' => $this->variables,
            'history' => $this->history,
        ]);

        return $this;
    }

    public function updateVariablesIn(
        array $values,
        PromiseInterface $promise
    ): JobUnitInterface {
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
                    $value
                );
            }
        };

        try {
            $updateClosure($values, $updateClosure);
            $promise->success($values);
        } catch (\Throwable $error) {
            $promise->fail($error);
        }

        return $this;
    }
}
