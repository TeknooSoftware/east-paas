<?php

declare(strict_types=1);

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

namespace Teknoo\East\Paas\Compilation\Compiler;

use DateTimeZone;
use DomainException;
use InvalidArgumentException;
use SensitiveParameter;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\CompletionMode;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\ConcurrencyPolicy;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\Planning;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\ScheduleOptions;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\SuccessCondition;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompilerInterface;
use Teknoo\East\Paas\Contracts\Compilation\ExtenderInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\Recipe\Promise\Promise;
use Throwable;

use function array_diff_key;
use function array_flip;
use function array_key_exists;
use function array_key_first;
use function array_map;
use function hash;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function preg_match;
use function substr;

/**
 * Compilation module able to convert `jobs` sections in paas.yaml file as Job instance.
 * The Job instance will be pushed into the CompiledDeploymentInterface instance. Pods of Job instance are compiled by
 * the Pod Compiler, but they are not injected into the CompiledDeploymentInterface instance (juste created volume).
 * Pods are only fetchable from Job.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class JobCompiler implements CompilerInterface, ExtenderInterface
{
    use MergeTrait;

    private const string KEY_EXTENDS = 'extends';

    private const string KEY_PODS = 'pods';

    private const string KEY_COMPLETIONS = 'completions';

    private const string KEY_COMPLETIONS_MODE = 'mode';

    private const string KEY_COMPLETIONS_COUNT = 'count';

    private const string KEY_COMPLETIONS_SUCCESS = 'success-on';

    private const string KEY_COMPLETIONS_FAILURE = 'fail-on';

    private const string KEY_COMPLETIONS_LIMIT_ON = 'limit-on';

    private const string KEY_COMPLETIONS_TIME_LIMIT = 'time-limit';

    private const string KEY_COMPLETIONS_SHELF_LIFE = 'shelf-life';

    private const string KEY_PARALLEL = 'is-parallel';

    private const string KEY_PLANNING = 'planning';

    private const string KEY_PLANNING_SCHEDULE = 'schedule';

    private const string KEY_SCHEDULE_OPTIONS = 'schedule-options';

    private const string KEY_SCHEDULE_OPTIONS_TIME_ZONE = 'time-zone';

    private const string KEY_SCHEDULE_OPTIONS_CONCURRENCY = 'concurrency';

    private const string KEY_SCHEDULE_OPTIONS_STARTING_DEADLINE = 'starting-deadline';

    private const string KEY_SCHEDULE_OPTIONS_SUCCESSFUL_HISTORY = 'successful-history';

    private const string KEY_SCHEDULE_OPTIONS_FAILED_HISTORY = 'failed-history';

    private const string KEY_SCHEDULE_OPTIONS_SUSPEND = 'suspend';

    /**
     * Descriptors accepted by Kubernetes in place of the 5 fields, case sensitive, `@every` takes a Go duration
     */
    private const string SCHEDULE_DESCRIPTOR_PATTERN
        = '/^@(yearly|annually|monthly|weekly|daily|midnight|hourly|every (\d+(\.\d+)?(ns|us|µs|ms|s|m|h))+)$/uD';

    /**
     * Values of each field of a schedule: minute, hour, day of month, month and day of week
     */
    private const array SCHEDULE_FIELDS_VALUES = [
        '[0-5]?\d',
        '[01]?\d|2[0-3]',
        '0?[1-9]|[12]\d|3[01]',
        '0?[1-9]|1[0-2]|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec',
        '0?[0-6]|sun|mon|tue|wed|thu|fri|sat',
    ];

    /**
     * @param array<string, array<string, mixed>> $jobsLibrary
     */
    public function __construct(
        private readonly PodCompiler $podCompiler,
        private readonly array $jobsLibrary,
    ) {
    }

    public function compile(
        #[SensitiveParameter] array &$definitions,
        CompiledDeploymentInterface $compiledDeployment,
        #[SensitiveParameter] JobWorkspaceInterface $workspace,
        #[SensitiveParameter] JobUnitInterface $job,
        ResourceManager $resourceManager,
        DefaultsBag $defaultsBag,
    ): CompilerInterface {
        foreach ($definitions as $name => &$config) {
            $pods = [];

            $hashName = substr(hash('sha256', $name), 0, 5);
            $this->podCompiler->processSetOfPods(
                definitions: $config[self::KEY_PODS],
                compiledDeployment: $compiledDeployment,
                workspace: $workspace,
                job: $job,
                resourceManager: $resourceManager,
                defaultsBag: $defaultsBag,
                promise: new Promise(
                    function (Pod $pod) use (&$pods): void {
                        $pods[$pod->getName()] = $pod;
                    },
                    fn (Throwable $throwable) => throw $throwable,
                )->allowReuse(),
                parentHashName: $hashName
            );

            if (empty($pods)) {
                throw new DomainException("teknoo.east.paas.error.recipe.job.no-pod", 400);
            }

            $successCondition = null;
            if (
                !empty($config[self::KEY_COMPLETIONS][self::KEY_COMPLETIONS_SUCCESS])
                || !empty($config[self::KEY_COMPLETIONS][self::KEY_COMPLETIONS_FAILURE])
            ) {
                $successCondition = new SuccessCondition(
                    successExitCode: array_map(
                        static fn (mixed $value): int => (int) $value,
                        (array) ($config[self::KEY_COMPLETIONS][self::KEY_COMPLETIONS_SUCCESS] ?? [0]),
                    ),
                    failureExistCode: array_map(
                        static fn (mixed $value): int => (int) $value,
                        (array) ($config[self::KEY_COMPLETIONS][self::KEY_COMPLETIONS_FAILURE] ?? [1]),
                    ),
                    containerName: $config[self::KEY_COMPLETIONS][self::KEY_COMPLETIONS_LIMIT_ON] ?? null,
                );
            }

            $planning = null;
            if (isset($config[self::KEY_PLANNING])) {
                $planning = Planning::from($config[self::KEY_PLANNING]);
            }

            $planningSchedule = null;
            if (isset($config[self::KEY_PLANNING_SCHEDULE])) {
                $planning ??= Planning::Scheduled;
                $planningSchedule = $config[self::KEY_PLANNING_SCHEDULE];
            }

            $planning ??= Planning::DuringDeployment;

            if (empty($planningSchedule) && $planning === Planning::Scheduled) {
                throw new DomainException("teknoo.east.paas.error.recipe.job.scheduling-not-configured", 400);
            }

            if (!empty($planningSchedule) && $planning === Planning::DuringDeployment) {
                throw new DomainException("teknoo.east.paas.error.recipe.job.scheduling-is-configured", 400);
            }

            if (null !== $planningSchedule && !self::isValidSchedule($planningSchedule)) {
                throw new DomainException(
                    "teknoo.east.paas.error.recipe.job.invalid-schedule:$name",
                    400
                );
            }

            $scheduleOptions = null;
            if (isset($config[self::KEY_SCHEDULE_OPTIONS])) {
                if ($planning !== Planning::Scheduled) {
                    throw new DomainException(
                        "teknoo.east.paas.error.recipe.job.schedule-options-without-scheduling:$name",
                        400
                    );
                }

                $scheduleOptions = self::buildScheduleOptions((string) $name, $config[self::KEY_SCHEDULE_OPTIONS]);
            }

            $completion = $config[self::KEY_COMPLETIONS] ?? [];

            $shelfLife = $completion[self::KEY_COMPLETIONS_SHELF_LIFE] ?? 60 * 60;
            if (
                array_key_exists(self::KEY_COMPLETIONS_SHELF_LIFE, $completion)
                && (
                    null === $completion[self::KEY_COMPLETIONS_SHELF_LIFE]
                    || 'null' === $completion[self::KEY_COMPLETIONS_SHELF_LIFE]
                )
            ) {
                $shelfLife = null;
            }

            $compiledDeployment->addJob(
                $name,
                new Job(
                    name: $name,
                    pods: $pods,
                    completionsCount: $completion[self::KEY_COMPLETIONS_COUNT] ?? 1,
                    isParallel: $config[self::KEY_PARALLEL] ?? false,
                    completion: CompletionMode::from(
                        $completion[self::KEY_COMPLETIONS_MODE] ?? CompletionMode::Common->value
                    ),
                    successCondition: $successCondition,
                    timeLimit: $completion[self::KEY_COMPLETIONS_TIME_LIMIT] ?? null,
                    shelfLife: $shelfLife,
                    planning: $planning,
                    planningSchedule: $planningSchedule,
                    scheduleOptions: $scheduleOptions,
                ),
            );
        }

        return $this;
    }

    private static function buildScheduleOptions(string $name, mixed $options): ScheduleOptions
    {
        $invalidOption = static fn (string $option): DomainException => new DomainException(
            "teknoo.east.paas.error.recipe.job.invalid-schedule-option:$name:$option",
            400
        );

        if (!is_array($options)) {
            throw $invalidOption(self::KEY_SCHEDULE_OPTIONS);
        }

        $unknownOptions = array_diff_key(
            $options,
            array_flip([
                self::KEY_SCHEDULE_OPTIONS_TIME_ZONE,
                self::KEY_SCHEDULE_OPTIONS_CONCURRENCY,
                self::KEY_SCHEDULE_OPTIONS_STARTING_DEADLINE,
                self::KEY_SCHEDULE_OPTIONS_SUCCESSFUL_HISTORY,
                self::KEY_SCHEDULE_OPTIONS_FAILED_HISTORY,
                self::KEY_SCHEDULE_OPTIONS_SUSPEND,
            ]),
        );

        if (!empty($unknownOptions)) {
            throw $invalidOption((string) array_key_first($unknownOptions));
        }

        //Only time zones of the IANA database are accepted by Kubernetes, not offsets or abbreviations
        $timeZone = $options[self::KEY_SCHEDULE_OPTIONS_TIME_ZONE] ?? null;
        if (
            null !== $timeZone
            && (
                !is_string($timeZone)
                || !in_array($timeZone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)
            )
        ) {
            throw $invalidOption(self::KEY_SCHEDULE_OPTIONS_TIME_ZONE);
        }

        $concurrency = null;
        if (isset($options[self::KEY_SCHEDULE_OPTIONS_CONCURRENCY])) {
            $concurrency = ConcurrencyPolicy::tryFrom((string) $options[self::KEY_SCHEDULE_OPTIONS_CONCURRENCY]);
            if (null === $concurrency) {
                throw $invalidOption(self::KEY_SCHEDULE_OPTIONS_CONCURRENCY);
            }
        }

        $integers = [];
        foreach (
            [
                self::KEY_SCHEDULE_OPTIONS_STARTING_DEADLINE,
                self::KEY_SCHEDULE_OPTIONS_SUCCESSFUL_HISTORY,
                self::KEY_SCHEDULE_OPTIONS_FAILED_HISTORY,
            ] as $option
        ) {
            $value = $options[$option] ?? null;
            if (null !== $value && (!is_int($value) || $value < 0)) {
                throw $invalidOption($option);
            }

            $integers[$option] = $value;
        }

        $suspend = $options[self::KEY_SCHEDULE_OPTIONS_SUSPEND] ?? null;
        if (null !== $suspend && !is_bool($suspend)) {
            throw $invalidOption(self::KEY_SCHEDULE_OPTIONS_SUSPEND);
        }

        return new ScheduleOptions(
            timeZone: $timeZone,
            concurrency: $concurrency,
            startingDeadline: $integers[self::KEY_SCHEDULE_OPTIONS_STARTING_DEADLINE],
            successfulHistory: $integers[self::KEY_SCHEDULE_OPTIONS_SUCCESSFUL_HISTORY],
            failedHistory: $integers[self::KEY_SCHEDULE_OPTIONS_FAILED_HISTORY],
            suspend: $suspend,
        );
    }

    /**
     * Checks the schedule like Kubernetes (robfig/cron's standard parser): a descriptor (`@daily`, ...,
     * `@every <duration>`) or 5 fields (minute, hour, day of month, month, day of week), each one a list of `*`, `?`,
     * values or ranges, with an optional step. A time zone in the schedule (`TZ=`, `CRON_TZ=`) is refused, like
     * Kubernetes does. A reversed range (`5-1`) is not detected here, Kubernetes refuses it.
     */
    private static function isValidSchedule(mixed $schedule): bool
    {
        if (!is_string($schedule)) {
            return false;
        }

        if (1 === preg_match(self::SCHEDULE_DESCRIPTOR_PATTERN, $schedule)) {
            return true;
        }

        $fields = array_map(
            static function (string $value): string {
                $item = "(\*|\?|($value)(-($value))?)(\/0*[1-9]\d*)?";

                return "$item(,$item)*";
            },
            self::SCHEDULE_FIELDS_VALUES,
        );

        return 1 === preg_match('/^\s*' . implode('\s+', $fields) . '\s*$/iD', $schedule);
    }

    public function extends(array &$definitions): ExtenderInterface
    {
        foreach ($definitions as &$config) {
            if (isset($config[self::KEY_EXTENDS])) {
                $libName = $config[self::KEY_EXTENDS];
                if (!is_string($libName)) {
                    throw new InvalidArgumentException("teknoo.east.paas.error.recipe.job.extends-need-string", 400);
                }

                if (!isset($this->jobsLibrary[$libName])) {
                    throw new DomainException(
                        "teknoo.east.paas.error.recipe.job.extends-not-available:$libName",
                        400
                    );
                }

                $config = self::arrayMergeRecursiveDistinct($this->jobsLibrary[$libName], $config);
            }
        }

        return $this;
    }
}
