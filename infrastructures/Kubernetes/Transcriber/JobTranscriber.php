<?php

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

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Teknoo\East\Foundation\Time\SleepServiceInterface;
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
use Teknoo\Kubernetes\Model\DeleteOptions;
use Teknoo\Kubernetes\Model\Job as JobModel;
use Teknoo\Kubernetes\Repository\Repository;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

/**
 * "Deployment transcriber" to translate CompiledDeployment's non scheduled job to Kubernetes job manifest.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class JobTranscriber implements DeploymentInterface
{
    use JobTranscriberTrait;

    private const string NAME_SUFFIX = '-job';

    private const string POD_SUFFIX = '-pod-job';

    private ?SleepServiceInterface $sleepService = null;

    public function setSleepService(SleepServiceInterface $sleepService): self
    {
        $this->sleepService = $sleepService;

        return $this;
    }

    /**
     * @param array<string, array<string, Image>>|Image[][] $images
     * @param array<string, Volume> $volumes
     */
    private static function convertToJob(
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
    ): JobModel {
        $spec = self::writeJobSpec(
            job: $job,
            pod: $pod,
            images: $images,
            volumes: $volumes,
            prefixer: $prefixer,
            requireLabel: $requireLabel,
            defaultsBag: $defaultsBag,
        );

        $spec['metadata'] = [
            'name' => $name . self::NAME_SUFFIX,
            'namespace' => $namespace,
            'labels' => [
                'name' => $prefixer($job->getName()),
            ],
            'annotations' => [
                'teknoo.east.paas.version' => 'v' . $version,
            ],
        ];

        return new JobModel($spec);
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
        $sleepService = $this->sleepService;

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
                $sleepService,
            ): void {
                if ($job->getPlanning() !== Planning::DuringDeployment) {
                    return;
                }

                $prefixer = self::createPrefixer($prefix);
                $resultsSet = [];
                try {
                    if (!empty($namespace)) {
                        $client->setNamespace($namespace);
                    }

                    foreach ($job->getPods() as $pod) {
                        $name = $prefixer($job->getName() . '-' . $pod->getName());
                        /** @var Repository<JobModel> $dRepository */
                        $dRepository = $client->jobs();

                        $previousJob = null;
                        $oldVersion = 0;
                        $version = self::getVersion($name, $dRepository, $oldVersion, $previousJob);

                        /** @var array<array<Image>> $images */
                        /** @var array<string, Volume> $volumes */
                        $kubeSet = self::convertToJob(
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
                        );

                        if ($dRepository->exists($kubeSet->getMetadata('name') ?? $name . self::NAME_SUFFIX)) {
                            $dRepository->delete(
                                $kubeSet,
                                new DeleteOptions([
                                    'cascade' => 'orphan',
                                    'gracePeriodSeconds' => 0,
                                ])
                            );

                            $sleepService?->wait(2);
                        }

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
