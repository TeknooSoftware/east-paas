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

use DomainException;
use InvalidArgumentException;
use SensitiveParameter;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\CompletionMode;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\Planning;
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

use function array_key_exists;
use function array_map;
use function count;
use function explode;
use function hash;
use function in_array;
use function is_array;
use function is_string;
use function preg_match;
use function preg_split;
use function str_starts_with;
use function strtolower;
use function substr;
use function trim;

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

    /**
     * Descriptors accepted by Kubernetes (robfig/cron's standard parser) in place of the 5 fields
     */
    private const array SCHEDULE_DESCRIPTORS = [
        '@yearly',
        '@annually',
        '@monthly',
        '@weekly',
        '@daily',
        '@midnight',
        '@hourly',
    ];

    /**
     * Bounds and names of each field of a schedule: minute, hour, day of month, month and day of week
     *
     * @var array<int, array{min: int, max: int, names: array<string, int>}>
     */
    private const array SCHEDULE_FIELDS = [
        ['min' => 0, 'max' => 59, 'names' => []],
        ['min' => 0, 'max' => 23, 'names' => []],
        ['min' => 1, 'max' => 31, 'names' => []],
        [
            'min' => 1,
            'max' => 12,
            'names' => [
                'jan' => 1,
                'feb' => 2,
                'mar' => 3,
                'apr' => 4,
                'may' => 5,
                'jun' => 6,
                'jul' => 7,
                'aug' => 8,
                'sep' => 9,
                'oct' => 10,
                'nov' => 11,
                'dec' => 12,
            ],
        ],
        [
            'min' => 0,
            'max' => 6,
            'names' => [
                'sun' => 0,
                'mon' => 1,
                'tue' => 2,
                'wed' => 3,
                'thu' => 4,
                'fri' => 5,
                'sat' => 6,
            ],
        ],
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
                ),
            );
        }

        return $this;
    }

    /**
     * Checks the schedule as Kubernetes does (robfig/cron's standard parser): 5 fields (minute, hour, day of month,
     * month, day of week), or a descriptor (`@daily`, ..., `@every <duration>`). A time zone in the schedule
     * (`TZ=`, `CRON_TZ=`) is refused, like Kubernetes does.
     */
    private static function isValidSchedule(mixed $schedule): bool
    {
        if (!is_string($schedule)) {
            return false;
        }

        if (in_array($schedule, self::SCHEDULE_DESCRIPTORS, true)) {
            return true;
        }

        if (str_starts_with($schedule, '@every ')) {
            return 1 === preg_match('/^(\d+(\.\d+)?(ns|us|µs|ms|s|m|h))+$/u', substr($schedule, 7));
        }

        $fields = preg_split('/\s+/', trim($schedule));
        if (!is_array($fields) || count($fields) !== count(self::SCHEDULE_FIELDS)) {
            return false;
        }

        foreach ($fields as $index => $field) {
            foreach (explode(',', $field) as $item) {
                if (!self::isValidScheduleItem($item, self::SCHEDULE_FIELDS[$index])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * An item is `*`, `?`, a value or a range of values (`a-b`), optionally followed by a step (`/n`)
     *
     * @param array{min: int, max: int, names: array<string, int>} $bounds
     */
    private static function isValidScheduleItem(string $item, array $bounds): bool
    {
        $rangeAndStep = explode('/', $item);
        if (count($rangeAndStep) > 2) {
            return false;
        }

        if (isset($rangeAndStep[1])) {
            $step = self::parseScheduleValue($rangeAndStep[1], []);
            if (null === $step || $step < 1) {
                return false;
            }
        }

        if ('*' === $rangeAndStep[0] || '?' === $rangeAndStep[0]) {
            return true;
        }

        $lowAndHigh = explode('-', $rangeAndStep[0]);
        if (count($lowAndHigh) > 2) {
            return false;
        }

        $start = self::parseScheduleValue($lowAndHigh[0], $bounds['names']);
        $end = $start;
        if (isset($lowAndHigh[1])) {
            $end = self::parseScheduleValue($lowAndHigh[1], $bounds['names']);
        }

        return null !== $start
            && null !== $end
            && $start >= $bounds['min']
            && $end <= $bounds['max']
            && $start <= $end;
    }

    /**
     * @param array<string, int> $names
     */
    private static function parseScheduleValue(string $value, array $names): ?int
    {
        $lowerValue = strtolower($value);
        if (isset($names[$lowerValue])) {
            return $names[$lowerValue];
        }

        if (1 !== preg_match('/^\d+$/', $value)) {
            return null;
        }

        return (int) $value;
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
