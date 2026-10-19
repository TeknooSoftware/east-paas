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

use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\Image;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\ConcurrencyPolicy;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\Planning;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\VolumeInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\AccumulatorInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\TranscriberInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\CronSchedule;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\JobRunSettings;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\ScheduledJobConfiguration;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

use function is_array;
use function is_string;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * "Deployment transcriber" translating CompiledDeployment's jobs into Compose services.
 *
 * During-deployment jobs are guarded by the `jobs` profile so a plain `docker compose up` does not start them; the
 * deploy playbook runs each completion of a pod with `jobs/east-paas-pod.sh`.
 *
 * Scheduled jobs (`Planning::Scheduled`) are guarded by the `scheduled` profile; the configuration of each pod is
 * pushed in `jobs/<anchor>.conf`, read by the runner called every minute by its crontab entry. A schedule
 * `@every <duration>` is not supported: the job is not deployed, with a warning.
 *
 * Each job pod becomes one or more services (anchor + sidecars) with `restart: "no"`, the job's profile and the
 * job's run settings (parallelism, completions, success/failure exit codes, time limit) recorded under
 * `x-paas-job`. `parallel` is not supported: runs are always sequential.
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

    private const string NETWORK_SERVICE_PREFIX = 'service:';

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

    /**
     * @return array<string, mixed>
     */
    private static function scheduleMeta(Job $job, CronSchedule $cron): array
    {
        $meta = [
            'schedule' => $job->getPlanningSchedule(),
        ];

        if (null !== $cron->timeZone) {
            $meta['time_zone'] = $cron->timeZone;
        }

        $options = $job->getScheduleOptions();
        $meta['concurrency'] = ($options->concurrency ?? ConcurrencyPolicy::Allow)->value;
        $meta['suspend'] = true === $options?->suspend;

        if (null !== $options?->startingDeadline) {
            $meta['starting_deadline'] = $options->startingDeadline;
        }

        if (null !== $options?->successfulHistory) {
            $meta['successful_history'] = $options->successfulHistory;
        }

        if (null !== $options?->failedHistory) {
            $meta['failed_history'] = $options->failedHistory;
        }

        return $meta;
    }

    /**
     * @param PromiseInterface<array<string, mixed>, mixed> $promise
     */
    private static function warnNotDeployed(
        AccumulatorInterface $accumulator,
        PromiseInterface $promise,
        string $warning,
    ): void {
        $accumulator->addWarning($warning);

        $promise->success(['warning' => $warning]);
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        AccumulatorInterface $accumulator,
        PromiseInterface $promise,
        DefaultsBag $defaultsBag,
        string $namespace,
    ): TranscriberInterface {
        $networkName = $accumulator->getNetworkName();
        $secrets = self::collectSecrets($compiledDeployment);
        $maps = self::collectMaps($compiledDeployment);
        $skippedJobs = [];

        $compiledDeployment->foreachJob(
            /**
             * @param array<string, array<string, Image>> $images
             * @param array<string, VolumeInterface> $volumes
             */
            static function (
                Job $job,
                array $images,
                array $volumes,
                string $prefix,
            ) use (
                $accumulator,
                $promise,
                $networkName,
                $secrets,
                $maps,
                &$skippedJobs,
            ): void {
                //The callback is called once per pod of the job, an unsupported job is reported only once
                if (isset($skippedJobs[$job->getName()])) {
                    return;
                }

                $prefixer = self::createPrefixer($prefix);
                $jobMeta = self::jobMeta($job);
                $jobPrefix = (string) $prefixer($job->getName()) . '-';

                try {
                    $profile = self::PROFILE;
                    $cron = null;
                    $scheduleMeta = [];
                    if (Planning::Scheduled === $job->getPlanning()) {
                        $schedule = (string) $job->getPlanningSchedule();
                        $cron = CronSchedule::fromSchedule($schedule, $job->getScheduleOptions()?->timeZone);

                        if (null === $cron) {
                            $skippedJobs[$job->getName()] = true;

                            self::warnNotDeployed(
                                $accumulator,
                                $promise,
                                "The scheduled job `{$job->getName()}` uses the schedule `$schedule`, not supported "
                                . 'on a Docker Compose host, it is not deployed',
                            );

                            return;
                        }

                        $profile = AccumulatorInterface::SCHEDULED_PROFILE;
                        $scheduleMeta = self::scheduleMeta($job, $cron);
                    }

                    $emitted = [];

                    /** @var Pod $pod */
                    foreach ($job->getPods() as $pod) {
                        $podServices = [];

                        /** @var array<string, array<string, Image>> $images */
                        $services = self::podToServices(
                            pod: $pod,
                            images: $images,
                            prefixer: $prefixer,
                            networkName: $networkName,
                            accumulator: $accumulator,
                            deploymentVolumes: $volumes,
                            secrets: $secrets,
                            maps: $maps,
                            envFilePrefix: $jobPrefix,
                        );

                        foreach ($services as $serviceName => $serviceSpec) {
                            $jobServiceName = $jobPrefix . $serviceName;

                            $networkMode = $serviceSpec['network_mode'] ?? null;
                            if (
                                is_string($networkMode)
                                && str_starts_with($networkMode, self::NETWORK_SERVICE_PREFIX)
                            ) {
                                $serviceSpec['network_mode'] = self::NETWORK_SERVICE_PREFIX . $jobPrefix
                                    . substr($networkMode, strlen(self::NETWORK_SERVICE_PREFIX));
                            }

                            //Like Kubernetes, a job ignores the replicas of its pod: `up` would start several anchors,
                            //outside the network namespace of the sidecars
                            if (is_array($serviceSpec['deploy'] ?? null)) {
                                unset($serviceSpec['deploy']['replicas']);
                                if (empty($serviceSpec['deploy'])) {
                                    unset($serviceSpec['deploy']);
                                }
                            }

                            $serviceSpec['profiles'] = [$profile];
                            $serviceSpec['restart'] = 'no';
                            $serviceSpec['x-paas-job'] = $jobMeta;

                            if (null !== $cron) {
                                $serviceSpec['x-paas-schedule'] = $scheduleMeta;
                            }

                            $accumulator->addService($jobServiceName, $serviceSpec);

                            $emitted[$jobServiceName] = $serviceSpec;
                            $podServices[] = $jobServiceName;
                        }

                        if (null !== $cron && !empty($podServices)) {
                            $configuration = new ScheduledJobConfiguration(
                                project: $accumulator->getProjectName(),
                                services: $podServices,
                                run: JobRunSettings::fromMeta($jobMeta),
                                pattern: $cron->pattern,
                                options: $job->getScheduleOptions(),
                            );

                            $accumulator->addFile('jobs/' . $podServices[0] . '.conf', $configuration->toShell());
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
