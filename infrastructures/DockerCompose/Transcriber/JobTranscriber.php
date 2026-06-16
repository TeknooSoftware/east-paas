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

namespace Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber;

use Teknoo\East\Paas\Compilation\CompiledDeployment\Job;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\Planning;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\GenerationInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\TranscriberInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

/**
 * "Deployment transcriber" translating CompiledDeployment's during-deployment jobs into Compose services
 * guarded by the `jobs` profile so a plain `docker compose up` does not start them; the deploy playbook runs
 * them once with `docker compose --profile jobs run --rm <svc>`.
 *
 * Scheduled jobs (`Planning::Scheduled`) are skipped: they are re-dispatched platform-side by the East PaaS
 * worker (`symfony/scheduler`) rather than emitted into the Compose file. Each job pod becomes one or more
 * services (anchor + sidecars) with `restart: "no"`, the `jobs` profile and the job's run settings
 * (parallelism, completions, success/failure exit codes, time limit) recorded under `x-paas-job` for the
 * playbook to honour.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class JobTranscriber implements DeploymentInterface
{
    use CommonTrait;
    use PodsTranscriberTrait;

    private const string PROFILE = 'jobs';

    /**
     * @return array<string, mixed>
     */
    private static function jobMeta(Job $job): array
    {
        $meta = [
            'parallel' => $job->isParallel(),
            'completions' => $job->getCompletionsCount(),
        ];

        if (null !== ($timeLimit = $job->getTimeLimit())) {
            $meta['time_limit'] = $timeLimit;
        }

        if (null !== ($condition = $job->getSuccessCondition())) {
            if (!empty($condition->successExitCode)) {
                $meta['success_exit_codes'] = $condition->successExitCode;
            }

            if (!empty($condition->failureExistCode)) {
                $meta['failure_exit_codes'] = $condition->failureExistCode;
            }
        }

        return $meta;
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        GenerationInterface $generation,
        PromiseInterface $promise,
        DefaultsBag $defaultsBag,
        string $namespace,
    ): TranscriberInterface {
        $networkName = $generation->getDedicatedNetworkName();

        $compiledDeployment->foreachJob(
            static function (
                Job $job,
                array $images,
                array $volumes,
                string $prefix,
            ) use (
                $generation,
                $promise,
                $networkName,
            ): void {
                if (Planning::DuringDeployment !== $job->getPlanning()) {
                    return;
                }

                $prefixer = self::createPrefixer($prefix);
                $jobMeta = self::jobMeta($job);

                try {
                    $emitted = [];

                    /** @var Pod $pod */
                    foreach ($job->getPods() as $pod) {
                        /** @var array<string, array<string, \Teknoo\East\Paas\Compilation\CompiledDeployment\Image\Image>> $images */
                        $services = self::podToServices(
                            pod: $pod,
                            images: $images,
                            prefixer: $prefixer,
                            networkName: $networkName,
                        );

                        foreach ($services as $serviceName => $serviceSpec) {
                            $jobServiceName = (string) $prefixer($job->getName()) . '-' . $serviceName;

                            $serviceSpec['profiles'] = [self::PROFILE];
                            $serviceSpec['restart'] = 'no';
                            $serviceSpec['x-paas-job'] = $jobMeta;

                            $generation->addService($jobServiceName, $serviceSpec);

                            $emitted[$jobServiceName] = $serviceSpec;
                        }
                    }

                    $promise->success(['services' => $emitted]);
                } catch (Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
