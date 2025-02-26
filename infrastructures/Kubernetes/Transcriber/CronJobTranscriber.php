<?php

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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\Image;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\Planning;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberInterface;
use Teknoo\Kubernetes\Client as KubernetesClient;
use Teknoo\Kubernetes\Model\CronJob;
use Teknoo\Kubernetes\Repository\Repository;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class CronJobTranscriber implements DeploymentInterface
{
    use JobTranscriberTrait;

    private const NAME_SUFFIX = '-cronjob';

    /**
     * @param array<string, array<string, Image>>|Image[][] $images
     * @param array<string, Volume>|Volume[] $volumes
     * @return array<string, mixed>
     */
    protected static function writeCronJobSpec(
        string $name,
        Job $job,
        Pod $pod,
        array $images,
        array $volumes,
        string $namespace,
        int $version,
        callable $prefixer,
        string $requireLabel,
        DefaultsBag $defaultsBag,
    ): array {
        $specs = [
            'spec' => [
                'schedule' => $job->getPlanningScheduled(),
                'jobTemplate' => self::writeJobSpec(
                    job: $job,
                    pod: $pod,
                    images: $images,
                    volumes: $volumes,
                    namespace: $namespace,
                    version: $version,
                    prefixer: $prefixer,
                    requireLabel: $requireLabel,
                    defaultsBag: $defaultsBag,
                )
            ],
        ];

        return $specs;
    }

    /**
     * @param array<string, array<string, Image>>|Image[][] $images
     * @param array<string, Volume>|Volume[] $volumes
     * @return iterable<CronJob>
     */
    private static function convertToCronJob(
        string $name,
        Job $job,
        array $images,
        array $volumes,
        string $namespace,
        int $version,
        callable $prefixer,
        string $requireLabel,
        DefaultsBag $defaultsBag,
    ): iterable {
        $final = [];

        foreach ($job->getPods() as $pod) {
            $final[] = new CronJob(
                static::writeCronJobSpec(
                    name: $name,
                    job: $job,
                    pod: $pod,
                    images: $images,
                    volumes: $volumes,
                    namespace: $namespace,
                    version: $version,
                    prefixer: $prefixer,
                    requireLabel: $requireLabel,
                    defaultsBag: $defaultsBag,
                )
            );
        }

        return $final;
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        KubernetesClient $client,
        PromiseInterface $promise,
        DefaultsBag $defaultsBag,
        string $namespace,
        bool $useHierarchicalNamespaces,
    ): TranscriberInterface {
        $requireLabel = $this->requireLabel;
        $compiledDeployment->foreachJob(
            static function (
                Job $job,
                array $images,
                array $volumes,
                string $prefix,
            ) use (
                $client,
                $namespace,
                $promise,
                $requireLabel,
                $defaultsBag,
            ): void {
                if ($job->getPlanning() !== Planning::Scheduled) {
                    return;
                }

                $prefixer = self::createPrefixer($prefix);
                try {
                    if (!empty($namespace)) {
                        $client->setNamespace($namespace);
                    }

                    $name = $prefixer($job->getName());
                    /** @var Repository<CronJob> $dRepository */
                    $dRepository = $client->cronJobs();

                    $previousJob = null;
                    $oldVersion = 0;
                    $version = self::getVersion($name, $dRepository, $oldVersion, $previousJob);
                } catch (Throwable $error) {
                    $promise->fail($error);

                    return;
                }

                $kubeSets = self::convertToCronJob(
                    name: $name,
                    job: $job,
                    images: $images,
                    volumes: $volumes,
                    namespace: $namespace,
                    version: $version,
                    prefixer: $prefixer,
                    requireLabel:$requireLabel,
                    defaultsBag: $defaultsBag,
                );

                try {
                    $resultsSet = [];
                    foreach ($kubeSets as $kubeSet) {
                        $result = $dRepository->apply($kubeSet);

                        $resultsSet[] = self::cleanResult($result);
                    }

                    $promise->success($resultsSet);
                } catch (Throwable $throwable) {
                    $promise->fail($throwable);
                }
            }
        );

        return $this;
    }
}
