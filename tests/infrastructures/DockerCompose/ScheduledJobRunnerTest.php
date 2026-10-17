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

namespace Teknoo\Tests\East\Paas\Infrastructures\DockerCompose;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\ConcurrencyPolicy;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\ScheduleOptions;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\CronSchedule;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\JobRunSettings;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\ScheduledJobConfiguration;

use function array_filter;
use function array_keys;
use function array_map;
use function array_slice;
use function array_values;
use function explode;
use function fclose;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function fileinode;
use function flock;
use function fopen;
use function in_array;
use function is_array;
use function mkdir;
use function preg_grep;
use function preg_match;
use function preg_replace;
use function range;
use function scandir;
use function sort;
use function str_ends_with;
use function str_starts_with;
use function symlink;
use function time;
use function trim;
use function unlink;
use function usleep;

use const LOCK_EX;
use const LOCK_NB;
use const LOCK_UN;

/**
 * Runs `jobs/east-paas-cron.sh` and `jobs/east-paas-pod.sh`, extracted from the deploy playbook template, like their
 * crontab entry, with fake `docker`, `flock`, `timeout`, `sh` and `date` in a hermetic PATH. The fake `date` answers
 * from a table computed with DateTimeImmutable, the zone of the host being UTC: the downtimes, the deadlines and the
 * changes of time are deterministic.
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversNothing]
class ScheduledJobRunnerTest extends TestCase
{
    use JobScriptTestTrait;

    private const string ANCHOR = 'job-backup';

    private const string SCAN_FORMAT = '+%M %H %d %m %w';

    /**
     * Fake `date` answering from the table `FAKE_DATES`; `FAKE_CANCEL_ON_START` acts like a newer run of `replace`
     * before the first completion of this one
     */
    private const string FAKE_DATE = <<<'SH'
        #!/bin/sh
        zone=
        if [ -n "${TZ+set}" ]; then
            zone="TZ=$TZ"
        fi
        echo "[$zone] $*" >>"$FAKE_DATE_LOG"

        case $* in
            *' +%M %H %d %m %w')
                for lock in ${FAKE_PROBE_LOCKS:-}; do
                    # Without a command to run: the hermetic PATH has no `true`
                    (exec 7>>"$FAKE_STATE/$lock" && flock -n 7)
                    case $? in
                        0) echo "date: $lock free" >>"$FAKE_LOG" ;;
                        1) echo "date: $lock busy" >>"$FAKE_LOG" ;;
                        *) echo "date: $lock not probed" >>"$FAKE_LOG" ;;
                    esac
                done
                ;;
        esac

        if [ -n "${FAKE_CANCEL_ON_START:-}" ]; then
            for log in "$FAKE_STATE"/*.running.log; do
                if [ -e "$log" ]; then
                    : >"$FAKE_CANCEL"
                fi
            done
        fi

        case " $* " in
            *' -d '*)
                if [ -n "${FAKE_DATE_BSD:-}" ]; then
                    echo "date: illegal option -- d" >&2
                    exit 1
                fi
                key="[$zone] $*="
                ;;
            ' +%s ')
                echo "$FAKE_NOW"
                exit 0
                ;;
            *)
                key="[$zone] -d @$FAKE_NOW $*="
                ;;
        esac

        if [ "$*" = '-d @0 +%s' ]; then
            echo 0
            exit 0
        fi

        line=$(grep -F -e "$key" "$FAKE_DATES") || exit 1
        echo "${line#"$key"}"

        SH;

    /**
     * `FAKE_NEWER_LAST` acts like a newer run of `replace` while this one waits for the lock of the runs
     */
    private const string FAKE_FLOCK = <<<'SH'
        #!/bin/sh
        echo "flock $*" >>"$FAKE_LOG"
        if [ "$1" = "-n" ] && [ -n "${FAKE_FLOCK_BUSY:-}" ]; then
            exit 1
        fi
        if [ "$1" = "9" ] && [ -n "${FAKE_NEWER_LAST:-}" ]; then
            echo "$FAKE_NEWER_LAST" >"$FAKE_STATE/last"
        fi
        exit 0

        SH;

    private const string FAKE_SH = <<<'SH'
        #!/bin/sh
        echo "sh $*" >>"$FAKE_LOG"
        exec "$FAKE_SHELL" "$@"

        SH;

    private const string STATE_LOCK = 'flock 8';

    private const string STATE_UNLOCK = 'flock -u 8';

    /**
     * @var array<string, string>
     */
    private array $dates = [];

    private ?string $zone = null;

    /**
     * Seconds before `now` answered by the fake `date`
     */
    private int $span = 3 * 3600;

    protected function setUp(): void
    {
        $this->createWorkingDirectory(
            [
                'flock' => self::FAKE_FLOCK,
                'date' => self::FAKE_DATE,
                'sh' => self::FAKE_SH,
            ],
            ['cat', 'grep', 'mv', 'rm', 'tail', 'wc'],
            ['jobs/east-paas-cron.sh', 'jobs/east-paas-pod.sh'],
        );

        mkdir($this->state(), 0700, true);
    }

    private static function at(string $time): int
    {
        return (new DateTimeImmutable($time))->getTimestamp();
    }

    private function state(string $file = ''): string
    {
        $path = $this->dir . '/project/jobs/' . self::ANCHOR;
        if ('' === $file) {
            return $path;
        }

        return $path . '/' . $file;
    }

    /**
     * @param array<mixed> $jobMeta
     * @param list<string> $services
     */
    private function configure(
        string $schedule,
        ?ScheduleOptions $options = null,
        array $jobMeta = [],
        array $services = [self::ANCHOR],
    ): void {
        $cron = CronSchedule::fromSchedule($schedule, $options?->timeZone);
        self::assertInstanceOf(CronSchedule::class, $cron);
        $this->zone = $cron->timeZone;

        $configuration = new ScheduledJobConfiguration(
            project: 'prj',
            services: $services,
            run: JobRunSettings::fromMeta($jobMeta),
            pattern: $cron->pattern,
            options: $options,
        );

        file_put_contents($this->dir . '/project/jobs/' . self::ANCHOR . '.conf', $configuration->toShell());
    }

    private function answerMinutes(int $from, int $to): void
    {
        $zone = new DateTimeZone($this->zone ?? 'UTC');
        $key = '[]';
        if (null !== $this->zone) {
            $key = '[TZ=' . $this->zone . ']';
        }

        for ($minute = $from - $from % 60; $minute <= $to; $minute += 60) {
            $instant = new DateTimeImmutable('@' . $minute);
            $this->dates[$key . ' -d @' . $minute . ' ' . self::SCAN_FORMAT] = $instant->setTimezone($zone)
                ->format('i H d m w');
            $this->dates['[] -u -d @' . $minute . ' +%Y%m%dT%H%M%SZ'] = $instant->format('Ymd\THis\Z');
        }
    }

    /**
     * A tick like the crontab entry, not started
     *
     * @param array<string, string> $env
     */
    private function tickProcess(string $shell, int $now, array $env = []): Process
    {
        $this->answerMinutes($now - $this->span, $now);
        $this->dates['[] -d @' . $now . ' -u +%Y-%m-%dT%H:%M:%SZ'] = (new DateTimeImmutable('@' . $now))
            ->format('Y-m-d\TH:i:s\Z');

        $table = '';
        foreach ($this->dates as $key => $answer) {
            $table .= $key . '=' . $answer . "\n";
        }

        file_put_contents($this->dir . '/dates.table', $table);

        return new Process(
            [
                $shell,
                '-c',
                '"$0" jobs/east-paas-cron.sh "$1" "$2" >>"jobs/$1/events.log" 2>&1',
                $shell,
                self::ANCHOR,
                'informative',
            ],
            $this->dir . '/project',
            [
                'PATH' => $this->hermeticPath(),
                'TZ' => false,
                'FAKE_NOW' => (string) $now,
                'FAKE_DATES' => $this->dir . '/dates.table',
                'FAKE_DATE_LOG' => $this->dir . '/dates.log',
                'FAKE_LOG' => $this->dir . '/calls.log',
                'FAKE_DIR' => $this->dir,
                'FAKE_STATE' => $this->state(),
                'FAKE_CANCEL' => $this->state('cancel'),
                'FAKE_SHELL' => $shell,
            ] + $env,
        );
    }

    /**
     * @param array<string, string> $env
     */
    private function tick(string $shell, int $now, array $env = []): int
    {
        $process = $this->tickProcess($shell, $now, $env);
        $process->run();

        return (int) $process->getExitCode();
    }

    private function useTheRealFlock(): void
    {
        $lookup = new Process(['/bin/sh', '-c', 'command -v flock']);
        $lookup->run();
        if (!$lookup->isSuccessful()) {
            self::markTestSkipped('flock (util-linux) is not installed on this host');
        }

        unlink($this->dir . '/bin/flock');
        symlink(trim($lookup->getOutput()), $this->dir . '/bin/flock');
    }

    /**
     * Holds a lock like another run or another tick
     *
     * @return resource
     */
    private function holdLock(string $lock)
    {
        $handle = fopen($this->state($lock), 'c');
        self::assertIsResource($handle);
        self::assertTrue(flock($handle, LOCK_EX | LOCK_NB));

        return $handle;
    }

    /**
     * @param resource $handle
     */
    private static function releaseLock($handle): void
    {
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    private function readState(string $file): ?string
    {
        if (!file_exists($this->state($file))) {
            return null;
        }

        return trim((string) file_get_contents($this->state($file)));
    }

    private function writeState(string $file, int|string $value): void
    {
        file_put_contents($this->state($file), $value . "\n");
    }

    /**
     * @return list<string>
     */
    private function historyLogs(): array
    {
        $logs = [];
        foreach ((array) scandir($this->state()) as $file) {
            if (str_ends_with((string) $file, '.log') && 'events.log' !== $file) {
                $logs[] = (string) $file;
            }
        }

        sort($logs);

        return $logs;
    }

    /**
     * @return list<string>
     */
    private static function lines(string $path): array
    {
        if (!file_exists($path)) {
            return [];
        }

        return array_values(array_filter(explode("\n", (string) file_get_contents($path)), 'strlen'));
    }

    /**
     * Lines of the events log, without their time
     *
     * @return list<string>
     */
    private function events(): array
    {
        return array_map(
            static fn (string $line): string => (string) preg_replace('#^\S+ #', '', $line),
            self::lines($this->state('events.log')),
        );
    }

    /**
     * @return list<string>
     */
    private function scanCalls(): array
    {
        return array_values(
            array_filter(
                self::lines($this->dir . '/dates.log'),
                static fn (string $call): bool => str_ends_with($call, ' ' . self::SCAN_FORMAT),
            ),
        );
    }

    /**
     * @return list<string>
     */
    private function scannedMinutes(): array
    {
        return array_map(
            static fn (string $call): string => (string) preg_replace('#^\[[^]]*\] -d @([0-9]+) .*$#', '$1', $call),
            $this->scanCalls(),
        );
    }

    /**
     * Calls of a completion of a pod of one service
     *
     * @return array<int, string>
     */
    private static function callsOfACompletion(int $container, string $limit = '0'): array
    {
        $wait = ['docker wait cid' . $container];
        if ('0' !== $limit) {
            //The fake timeout runs the command it limits
            $wait = ['timeout ' . $limit . ' ' . $wait[0], ...$wait];
        }

        return [
            'sh jobs/east-paas-pod.sh prj scheduled ' . $limit . ' job-backup',
            'docker compose -p prj --profile scheduled run -d --no-deps job-backup',
            ...$wait,
            'docker logs cid' . $container,
            'docker rm -f cid' . $container,
        ];
    }

    /**
     * Calls of a completion of a pod of two services
     *
     * @return array<int, string>
     */
    private static function callsOfAPodCompletion(): array
    {
        return [
            'sh jobs/east-paas-pod.sh prj scheduled 0 job-backup job-backup-proxy',
            'docker compose -p prj --profile scheduled up -d --force-recreate --no-deps job-backup job-backup-proxy',
            self::regularContainerLookup('job-backup'),
            self::regularContainerLookup('job-backup-proxy'),
            'docker wait id-job-backup id-job-backup-proxy',
            'docker logs id-job-backup',
            'docker logs id-job-backup-proxy',
            'docker rm -f id-job-backup id-job-backup-proxy',
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function replaceLookups(string ...$services): array
    {
        $calls = [];
        foreach ($services as $service) {
            $calls[] = 'docker ps -q --filter label=com.docker.compose.project=prj '
                . '--filter label=com.docker.compose.service=' . $service;
            //The previous run is warned before its containers are stopped
            $calls[] = 'cancel marker present';
        }

        return $calls;
    }

    public function testThePlaybookRunsTheRunnerEveryMinuteFromItsCrontabEntries(): void
    {
        $playbook = self::parseTemplate();
        self::assertArrayHasKey('paas_scheduled_jobs', $playbook['vars']);

        $keywords = ['name', 'loop', 'loop_control', 'register', 'when', 'changed_when', 'failed_when', 'no_log'];
        $positions = [];
        foreach (self::tasks() as $index => $task) {
            foreach (array_keys($task) as $key) {
                if (!in_array($key, $keywords, true)) {
                    self::assertStringStartsWith('ansible.builtin.', (string) $key);
                }
            }

            $file = $task['ansible.builtin.file'] ?? null;
            if (is_array($file)) {
                self::assertStringNotContainsString('cron', (string) ($file['path'] ?? ''));
            }

            if (is_array($file) && '{{ paas_project_dir }}/jobs/{{ item.service }}' === ($file['path'] ?? null)) {
                $positions['state'] = $index;
                self::assertSame('directory', $file['state']);
                self::assertSame('0750', $file['mode']);
                self::assertSame('{{ paas_scheduled_jobs | default([]) }}', $task['loop']);
            }

            $copy = $task['ansible.builtin.copy'] ?? null;
            if (is_array($copy) && '{{ paas_project_dir }}/jobs/east-paas-cron.sh' === ($copy['dest'] ?? null)) {
                $positions['runner'] = $index;
                self::assertSame('0750', $copy['mode']);
            }

            $cron = $task['ansible.builtin.cron'] ?? null;
            if (is_array($cron) && !isset($cron['state'])) {
                $positions['entries'] = $index;
                //Every minute: the five fields are not set, `*` by default
                self::assertSame(['name', 'job', 'disabled'], array_keys($cron));
                self::assertSame('cd {{ paas_project_dir | quote }} && {{ item.job }}', $cron['job']);
                self::assertSame('{{ item.disabled }}', $cron['disabled']);
            }
        }

        //The redirection of the entry to the events log needs the directory of the state of the job
        self::assertSame(['state', 'runner', 'entries'], array_keys($positions));

        $runner = self::extractScript('jobs/east-paas-cron.sh');
        self::assertTrue(str_starts_with($runner, "#!/bin/sh\n"));
        self::assertStringNotContainsString('{{', $runner);
        self::assertStringNotContainsString('{%', $runner);
        self::assertStringNotContainsString('{#', $runner);
    }

    public function testThePlaybookRemovesTheStateOfTheJobsNoLongerDeployedWithTheirEntries(): void
    {
        $removed = '';
        $anchors = '';
        $positions = [];
        foreach (self::tasks() as $index => $task) {
            $fact = $task['ansible.builtin.set_fact'] ?? null;
            if (is_array($fact) && isset($fact['paas_scheduled_jobs_removed'])) {
                $positions['list'] = $index;
                $removed = (string) $fact['paas_scheduled_jobs_removed'];
                self::assertSame('paas_crontab_command.rc == 0', $task['when']);
            }

            $file = $task['ansible.builtin.file'] ?? null;
            if (is_array($file) && 'absent' === ($file['state'] ?? null)) {
                $positions['state'] = $index;
                $anchors = (string) $task['loop'];
                self::assertSame('{{ paas_project_dir }}/jobs/{{ item }}', $file['path']);
                self::assertSame('paas_crontab_command.rc == 0', $task['when']);
            }

            $cron = $task['ansible.builtin.cron'] ?? null;
            if (is_array($cron) && 'absent' === ($cron['state'] ?? null)) {
                $positions['entries'] = $index;
                self::assertSame('{{ item }}', $cron['name']);
                self::assertSame('{{ paas_scheduled_jobs_removed | default([]) }}', $task['loop']);
                self::assertSame('paas_crontab_command.rc == 0', $task['when']);
            }
        }

        //Before the entries: the entry left by an interrupted deployment still leads the next one to its state
        self::assertSame(['list', 'state', 'entries'], array_keys($positions));
        self::assertStringContainsString(
            "difference((paas_scheduled_jobs | default([])) | map(attribute='name'))",
            $removed,
        );

        self::assertStringStartsWith('{{ (paas_scheduled_jobs_removed | default([]))', $anchors);
        self::assertStringContainsString("| map('regex_replace', '^east-paas:' ~ paas_project ~ ':', '')", $anchors);
        self::assertStringContainsString("| product(['', '.conf'])", $anchors);
        self::assertSame(1, preg_match("#\\| select\\('match', '([^']+)'\\)#", $anchors, $filter));
        foreach (['job-backup', 'prefix-job.pod_2', 'Job3'] as $anchor) {
            self::assertSame(1, preg_match('#' . $filter[1] . '#', $anchor), $anchor);
        }

        //An entry edited by hand never removes a path outside the state of a job
        foreach (['', '.', '..', '../secrets', 'job/..', '/etc', 'job backup'] as $anchor) {
            self::assertSame(0, preg_match('#' . $filter[1] . '#', $anchor), $anchor);
        }
    }

    #[DataProvider('provideShells')]
    public function testWrongNumberOfArguments(string $shell): void
    {
        $process = new Process([$shell, 'jobs/east-paas-cron.sh', self::ANCHOR], $this->dir . '/project');
        $process->run();

        self::assertSame(2, $process->getExitCode());
        self::assertSame([], $this->calls());
    }

    #[DataProvider('provideShells')]
    public function testAMissingConfigurationFails(string $shell): void
    {
        self::assertSame(2, $this->tick($shell, self::at('2026-02-01T02:17:03Z')));
        self::assertSame(['jobs/job-backup.conf is missing'], $this->events());
        self::assertSame([], $this->calls());
    }

    #[DataProvider('provideShells')]
    public function testTheFirstTickRunsTheCurrentMinuteWhenItIsScheduled(string $shell): void
    {
        $this->configure('17 2 * * *');
        $minute = self::at('2026-02-01T02:17:00Z');

        self::assertSame(0, $this->tick($shell, $minute + 3));
        self::assertSame(
            [self::STATE_LOCK, self::STATE_UNLOCK, ...self::callsOfACompletion(1)],
            $this->calls(),
        );
        self::assertSame((string) $minute, $this->readState('seen'));
        self::assertSame((string) $minute, $this->readState('last'));
        self::assertNull($this->readState('pending'));
        self::assertSame(['20260201T021700Z.succeeded.log'], $this->historyLogs());
        self::assertSame("output of cid1\n", file_get_contents($this->state('20260201T021700Z.succeeded.log')));
        self::assertSame(
            ['20260201T021700Z: started', '20260201T021700Z: succeeded, exit code 0'],
            $this->events(),
        );
        self::assertStringStartsWith(
            "2026-02-01T02:17:03Z 20260201T021700Z: started\n",
            (string) file_get_contents($this->state('events.log')),
        );

        //Without time zone, TZ stays unset (an empty TZ is UTC): the zone of the host
        self::assertSame([(string) $minute], $this->scannedMinutes());
        self::assertSame([], (array) preg_grep('#^\[TZ=#', self::lines($this->dir . '/dates.log')));
    }

    #[DataProvider('provideShells')]
    public function testTheFirstTickDoesNotRunAMinuteBeforeIt(string $shell): void
    {
        //Like a CronJob created at this minute
        $this->configure('16 2 * * *');
        $minute = self::at('2026-02-01T02:17:00Z');

        self::assertSame(0, $this->tick($shell, $minute + 3));
        self::assertSame([self::STATE_LOCK], $this->calls());
        self::assertSame((string) $minute, $this->readState('seen'));
        self::assertNull($this->readState('last'));
        self::assertSame([], $this->historyLogs());
        self::assertSame([], $this->events());
    }

    #[DataProvider('provideShells')]
    public function testEachTickExaminesTheMinutesSinceThePreviousOne(string $shell): void
    {
        $this->configure('17 2 * * *');
        $minute = self::at('2026-02-01T02:16:00Z');

        self::assertSame(0, $this->tick($shell, $minute + 1));
        self::assertSame(0, $this->tick($shell, $minute + 61));
        self::assertSame(0, $this->tick($shell, $minute + 121));

        self::assertSame(
            [(string) $minute, (string) ($minute + 60), (string) ($minute + 120)],
            $this->scannedMinutes(),
        );
        self::assertSame(['20260201T021700Z.succeeded.log'], $this->historyLogs());
        self::assertSame((string) ($minute + 120), $this->readState('seen'));
        self::assertSame((string) ($minute + 60), $this->readState('last'));
    }

    #[DataProvider('provideShells')]
    public function testNothingRunsWhenNoMinuteIsScheduled(string $shell): void
    {
        $this->configure('0 3 * * *');
        $minute = self::at('2026-02-01T02:17:00Z');
        $this->writeState('seen', $minute - 60);

        self::assertSame(0, $this->tick($shell, $minute + 3));
        self::assertSame([self::STATE_LOCK], $this->calls());
        self::assertSame((string) $minute, $this->readState('seen'));
        self::assertSame([], $this->historyLogs());
        self::assertSame([], $this->events());
    }

    #[DataProvider('provideShells')]
    public function testOnlyTheMostRecentTimeMissedDuringADowntimeIsCaughtUp(string $shell): void
    {
        $this->configure('0 * * * *');
        $minute = self::at('2026-02-01T02:17:00Z');
        $this->writeState('seen', self::at('2026-01-31T22:17:00Z'));

        self::assertSame(0, $this->tick($shell, $minute + 3));
        self::assertSame(
            [self::STATE_LOCK, self::STATE_UNLOCK, ...self::callsOfACompletion(1)],
            $this->calls(),
        );
        self::assertSame(['20260201T020000Z.succeeded.log'], $this->historyLogs());
        self::assertSame((string) self::at('2026-02-01T02:00:00Z'), $this->readState('last'));

        self::assertSame(
            array_map('strval', range($minute, self::at('2026-02-01T02:00:00Z'), -60)),
            $this->scannedMinutes(),
        );
    }

    #[DataProvider('provideShells')]
    public function testTheStartingDeadlineBoundsTheMinutesExamined(string $shell): void
    {
        $this->configure('0 * * * *', new ScheduleOptions(startingDeadline: 600));
        $now = self::at('2026-02-01T02:17:03Z');
        $this->writeState('seen', self::at('2026-01-31T02:17:00Z'));

        //02:00 is before now - 600 s: missed
        self::assertSame(0, $this->tick($shell, $now));
        self::assertSame([self::STATE_LOCK], $this->calls());
        self::assertSame(
            array_map('strval', range($now - 3, $now - 600, -60)),
            $this->scannedMinutes(),
        );
        self::assertSame([], $this->historyLogs());

        $this->configure('0 * * * *', new ScheduleOptions(startingDeadline: 1200));
        $this->writeState('seen', self::at('2026-01-31T02:17:00Z'));
        self::assertSame(0, $this->tick($shell, $now));
        self::assertSame(['20260201T020000Z.succeeded.log'], $this->historyLogs());
    }

    #[DataProvider('provideShells')]
    public function testTheMinuteAtTheStartingDeadlineIsExamined(string $shell): void
    {
        //A tick at its second 0, like cron, and a deadline of whole minutes: 02:00 is at now - deadline
        $this->configure('0 * * * *', new ScheduleOptions(startingDeadline: 600));
        $now = self::at('2026-02-01T02:10:00Z');
        $this->writeState('seen', self::at('2026-01-31T02:10:00Z'));

        self::assertSame(0, $this->tick($shell, $now));
        self::assertSame(
            array_map('strval', range($now, $now - 600, -60)),
            $this->scannedMinutes(),
        );
        self::assertSame(['20260201T020000Z.succeeded.log'], $this->historyLogs());
    }

    #[DataProvider('provideShells')]
    public function testWithoutStartingDeadlineTheScanIsBoundedOnlyByTheLastMinuteExamined(string $shell): void
    {
        $this->span = 86400;
        $this->configure('0 20 * * *');
        $minute = self::at('2026-02-01T02:17:00Z');
        $this->writeState('seen', self::at('2026-01-31T02:17:00Z'));

        self::assertSame(0, $this->tick($shell, $minute + 3));
        self::assertSame(['20260131T200000Z.succeeded.log'], $this->historyLogs());
        self::assertSame(
            array_map('strval', range($minute, self::at('2026-01-31T20:00:00Z'), -60)),
            $this->scannedMinutes(),
        );
    }

    #[DataProvider('provideShells')]
    public function testAPendingTimeTooLateIsMissed(string $shell): void
    {
        $this->configure(
            '17 2 * * *',
            new ScheduleOptions(concurrency: ConcurrencyPolicy::Forbid, startingDeadline: 300),
        );
        $scheduled = self::at('2026-02-01T02:17:00Z');
        $this->writeState('seen', $scheduled);
        $this->writeState('pending', $scheduled);

        self::assertSame(0, $this->tick($shell, $scheduled + 7 * 60 + 5));
        self::assertSame([self::STATE_LOCK], $this->calls());
        self::assertNull($this->readState('pending'));
        self::assertSame([], $this->historyLogs());
        self::assertSame(
            ['20260201T021700Z: missed, not started within the starting deadline of 300 seconds'],
            $this->events(),
        );

        self::assertSame(0, $this->tick($shell, $scheduled + 8 * 60 + 5));
        self::assertCount(1, $this->events());
    }

    #[DataProvider('provideShells')]
    public function testAPendingTimeWithinTheStartingDeadlineIsStarted(string $shell): void
    {
        $this->configure(
            '17 2 * * *',
            new ScheduleOptions(concurrency: ConcurrencyPolicy::Forbid, startingDeadline: 300),
        );
        $scheduled = self::at('2026-02-01T02:17:00Z');
        $this->writeState('seen', $scheduled + 180);
        $this->writeState('pending', $scheduled);

        self::assertSame(0, $this->tick($shell, $scheduled + 4 * 60 + 5));
        self::assertSame(['20260201T021700Z.succeeded.log'], $this->historyLogs());
        self::assertNull($this->readState('pending'));
    }

    #[DataProvider('provideShells')]
    public function testAPendingTimeIsStillStartedAtTheEndOfTheStartingDeadline(string $shell): void
    {
        $this->configure(
            '17 2 * * *',
            new ScheduleOptions(concurrency: ConcurrencyPolicy::Forbid, startingDeadline: 300),
        );
        $scheduled = self::at('2026-02-01T02:17:00Z');
        $this->writeState('seen', $scheduled + 240);
        $this->writeState('pending', $scheduled);

        //Too late only after T + deadline: the tick at its second 0 still starts it
        self::assertSame(0, $this->tick($shell, $scheduled + 300));
        self::assertSame(['20260201T021700Z.succeeded.log'], $this->historyLogs());
        self::assertNull($this->readState('pending'));
        self::assertSame((string) $scheduled, $this->readState('last'));
    }

    #[DataProvider('provideShells')]
    public function testForbidKeepsTheTimePendingUntilThePreviousRunEnds(string $shell): void
    {
        $this->configure('17 2 * * *', new ScheduleOptions(concurrency: ConcurrencyPolicy::Forbid));
        $scheduled = self::at('2026-02-01T02:17:00Z');
        $this->writeState('seen', $scheduled - 60);

        self::assertSame(0, $this->tick($shell, $scheduled + 5, ['FAKE_FLOCK_BUSY' => '1']));
        self::assertSame([self::STATE_LOCK, 'flock -n 9'], $this->calls());
        self::assertSame((string) $scheduled, $this->readState('pending'));
        self::assertNull($this->readState('last'));

        self::assertSame(0, $this->tick($shell, $scheduled + 65, ['FAKE_FLOCK_BUSY' => '1']));
        self::assertSame((string) $scheduled, $this->readState('pending'));
        self::assertSame(
            ['20260201T021700Z: pending, the previous run is still running (concurrency forbid)'],
            $this->events(),
        );

        //Without deadline, started at the first tick after the end of the previous run
        self::assertSame(0, $this->tick($shell, $scheduled + 3 * 3600 + 5));
        self::assertSame(
            [
                self::STATE_LOCK,
                'flock -n 9',
                self::STATE_LOCK,
                'flock -n 9',
                self::STATE_LOCK,
                'flock -n 9',
                self::STATE_UNLOCK,
                ...self::callsOfACompletion(1),
            ],
            $this->calls(),
        );
        self::assertSame(['20260201T021700Z.succeeded.log'], $this->historyLogs());
        self::assertNull($this->readState('pending'));
        self::assertSame((string) $scheduled, $this->readState('last'));
        self::assertSame(
            [
                '20260201T021700Z: pending, the previous run is still running (concurrency forbid)',
                '20260201T021700Z: started',
                '20260201T021700Z: succeeded, exit code 0',
            ],
            $this->events(),
        );
    }

    #[DataProvider('provideShells')]
    public function testANewerScheduledTimeSupersedesThePendingOne(string $shell): void
    {
        $this->configure('15,17 2 * * *', new ScheduleOptions(concurrency: ConcurrencyPolicy::Forbid));
        $newer = self::at('2026-02-01T02:17:00Z');
        $this->writeState('seen', $newer - 60);
        $this->writeState('pending', $newer - 120);

        self::assertSame(0, $this->tick($shell, $newer + 5, ['FAKE_FLOCK_BUSY' => '1']));
        self::assertSame((string) $newer, $this->readState('pending'));

        self::assertSame(0, $this->tick($shell, $newer + 65));
        self::assertSame(['20260201T021700Z.succeeded.log'], $this->historyLogs());
        self::assertNull($this->readState('pending'));
        self::assertSame(
            [
                '20260201T021700Z: pending, the previous run is still running (concurrency forbid)',
                '20260201T021700Z: started',
                '20260201T021700Z: succeeded, exit code 0',
            ],
            $this->events(),
        );
    }

    #[DataProvider('provideShells')]
    public function testForbidRunsWhenThePreviousRunHasEnded(string $shell): void
    {
        $this->configure('17 2 * * *', new ScheduleOptions(concurrency: ConcurrencyPolicy::Forbid));

        self::assertSame(0, $this->tick($shell, self::at('2026-02-01T02:17:05Z')));
        self::assertSame(
            [self::STATE_LOCK, 'flock -n 9', self::STATE_UNLOCK, ...self::callsOfACompletion(1)],
            $this->calls(),
        );
        self::assertFileExists($this->state('run.lock'));
        self::assertFileExists($this->state('state.lock'));
    }

    #[DataProvider('provideShells')]
    public function testReplaceStopsTheRunningContainersOfThePreviousRun(string $shell): void
    {
        $this->configure('17 2 * * *', new ScheduleOptions(concurrency: ConcurrencyPolicy::Replace));

        self::assertSame(
            0,
            $this->tick($shell, self::at('2026-02-01T02:17:05Z'), ['FAKE_PS_IDS' => 'old1 old2']),
        );
        self::assertSame(
            [
                self::STATE_LOCK,
                //Released before the wait: the next ticks are not blocked by this run
                self::STATE_UNLOCK,
                ...self::replaceLookups('job-backup'),
                'docker stop old1 old2',
                'flock 9',
                ...self::callsOfACompletion(1),
            ],
            $this->calls(),
        );

        //Removed once the lock is held, so this run is not replaced by its own marker
        self::assertFileDoesNotExist($this->state('cancel'));
        self::assertSame(['20260201T021700Z.succeeded.log'], $this->historyLogs());
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function provideCompletions(): iterable
    {
        foreach (self::provideShells() as $name => [$shell]) {
            yield 'after its only completion with ' . $name => [$shell, 1];
            yield 'with a completion left with ' . $name => [$shell, 2];
        }
    }

    #[DataProvider('provideCompletions')]
    public function testAReplacedRunIsDeleted(string $shell, int $completions): void
    {
        $this->configure(
            '17 2 * * *',
            new ScheduleOptions(concurrency: ConcurrencyPolicy::Replace),
            ['completions' => $completions],
        );

        //A newer run creates the marker and stops the container (SIGTERM, 143): not kept in the failed history
        self::assertSame(
            143,
            $this->tick(
                $shell,
                self::at('2026-02-01T02:17:05Z'),
                ['FAKE_CANCEL_ON_WAIT' => '1', 'FAKE_EXIT_CODE' => '143'],
            ),
        );
        self::assertSame(
            [
                self::STATE_LOCK,
                self::STATE_UNLOCK,
                ...self::replaceLookups('job-backup'),
                'flock 9',
                ...self::callsOfACompletion(1),
            ],
            $this->calls(),
        );

        //Like a deleted Job, it is not in the history; the marker belongs to the newer run
        self::assertSame([], $this->historyLogs());
        self::assertFileExists($this->state('cancel'));
        self::assertSame(['20260201T021700Z: started', '20260201T021700Z: replaced by a newer run'], $this->events());
    }

    #[DataProvider('provideShells')]
    public function testARunReplacedBeforeItsFirstCompletionIsDeleted(string $shell): void
    {
        $this->configure('17 2 * * *', new ScheduleOptions(concurrency: ConcurrencyPolicy::Replace));

        self::assertSame(
            143,
            $this->tick($shell, self::at('2026-02-01T02:17:05Z'), ['FAKE_CANCEL_ON_START' => '1']),
        );
        self::assertSame(
            [self::STATE_LOCK, self::STATE_UNLOCK, ...self::replaceLookups('job-backup'), 'flock 9'],
            $this->calls(),
        );
        self::assertSame([], $this->historyLogs());
        self::assertFileExists($this->state('cancel'));
        self::assertSame(['20260201T021700Z: started', '20260201T021700Z: replaced by a newer run'], $this->events());
    }

    #[DataProvider('provideShells')]
    public function testARunReplacedWhileWaitingForThePreviousOneDoesNotStart(string $shell): void
    {
        $this->configure('17 2 * * *', new ScheduleOptions(concurrency: ConcurrencyPolicy::Replace));
        $newer = (string) self::at('2026-02-01T02:18:00Z');

        self::assertSame(
            143,
            $this->tick($shell, self::at('2026-02-01T02:17:05Z'), ['FAKE_NEWER_LAST' => $newer]),
        );
        self::assertSame(
            [self::STATE_LOCK, self::STATE_UNLOCK, ...self::replaceLookups('job-backup'), 'flock 9'],
            $this->calls(),
        );
        self::assertSame([], $this->historyLogs());
        self::assertSame(['20260201T021700Z: replaced by a newer run before its start'], $this->events());
    }

    #[DataProvider('provideShells')]
    public function testTheMarkerOfReplaceIsIgnoredByTheOtherPolicies(string $shell): void
    {
        file_put_contents($this->state('cancel'), '');
        $this->configure('17 2 * * *', null, ['completions' => 2]);

        self::assertSame(0, $this->tick($shell, self::at('2026-02-01T02:17:05Z')));
        self::assertSame(
            [
                self::STATE_LOCK,
                self::STATE_UNLOCK,
                ...self::callsOfACompletion(1),
                ...self::callsOfACompletion(2),
            ],
            $this->calls(),
        );
    }

    #[DataProvider('provideShells')]
    public function testAllowRunsAPodOfOneServiceWithoutLock(string $shell): void
    {
        $this->configure('17 2 * * *', new ScheduleOptions(concurrency: ConcurrencyPolicy::Allow));

        self::assertSame(0, $this->tick($shell, self::at('2026-02-01T02:17:05Z')));
        self::assertSame(
            [self::STATE_LOCK, self::STATE_UNLOCK, ...self::callsOfACompletion(1)],
            $this->calls(),
        );
        self::assertFileDoesNotExist($this->state('run.lock'));
    }

    #[DataProvider('provideShells')]
    public function testAllowSerializesTheRunsOfAPodOfSeveralServices(string $shell): void
    {
        $this->configure('17 2 * * *', null, [], [self::ANCHOR, 'job-backup-proxy']);

        self::assertSame(0, $this->tick($shell, self::at('2026-02-01T02:17:05Z')));
        self::assertSame(
            [self::STATE_LOCK, self::STATE_UNLOCK, 'flock 9', ...self::callsOfAPodCompletion()],
            $this->calls(),
        );
        self::assertFileExists($this->state('run.lock'));
        self::assertSame(
            "progress of up\neast-paas: job-backup\noutput of id-job-backup\neast-paas: job-backup-proxy\n"
            . "output of id-job-backup-proxy\n",
            file_get_contents($this->state('20260201T021700Z.succeeded.log')),
        );
    }

    #[DataProvider('provideShells')]
    public function testReplaceStopsTheRunningContainersOfEveryServiceOfThePod(string $shell): void
    {
        $this->configure(
            '17 2 * * *',
            new ScheduleOptions(concurrency: ConcurrencyPolicy::Replace),
            [],
            [self::ANCHOR, 'job-backup-proxy'],
        );

        self::assertSame(
            0,
            $this->tick(
                $shell,
                self::at('2026-02-01T02:17:05Z'),
                ['FAKE_PS_IDS' => 'job-backup=old1 job-backup-proxy=old2 job-other=old3'],
            ),
        );
        self::assertSame(
            [
                self::STATE_LOCK,
                self::STATE_UNLOCK,
                ...self::replaceLookups('job-backup', 'job-backup-proxy'),
                'docker stop old1 old2',
                'flock 9',
                ...self::callsOfAPodCompletion(),
            ],
            $this->calls(),
        );
    }

    /**
     * @return iterable<string, array{string, ?int, ?int, list<string>}>
     */
    public static function provideHistories(): iterable
    {
        foreach (self::provideShells() as $name => [$shell]) {
            yield 'defaults of Kubernetes with ' . $name => [
                $shell,
                null,
                null,
                [
                    '20260125T021700Z.running.log',
                    '20260130T021700Z.succeeded.log',
                    '20260131T021700Z.succeeded.log',
                    '20260131T031700Z.failed.log',
                    '20260201T021700Z.succeeded.log',
                ],
            ];
            yield 'none with ' . $name => [$shell, 0, 0, ['20260125T021700Z.running.log']];
            yield 'custom with ' . $name => [
                $shell,
                5,
                2,
                [
                    '20260125T021700Z.running.log',
                    '20260128T021700Z.succeeded.log',
                    '20260129T021700Z.succeeded.log',
                    '20260130T021700Z.succeeded.log',
                    '20260131T001700Z.failed.log',
                    '20260131T021700Z.succeeded.log',
                    '20260131T031700Z.failed.log',
                    '20260201T021700Z.succeeded.log',
                ],
            ];
        }
    }

    /**
     * @param list<string> $kept
     */
    #[DataProvider('provideHistories')]
    public function testTheHistoriesKeepTheNewestRuns(string $shell, ?int $successful, ?int $failed, array $kept): void
    {
        foreach (['27', '28', '29', '30', '31'] as $day) {
            file_put_contents($this->state('202601' . $day . 'T021700Z.succeeded.log'), '');
        }

        foreach (['30T231700Z', '31T001700Z', '31T031700Z'] as $time) {
            file_put_contents($this->state('202601' . $time . '.failed.log'), '');
        }

        //The log of a run killed with its runner is never pruned
        file_put_contents($this->state('20260125T021700Z.running.log'), '');

        $this->configure('17 2 * * *', new ScheduleOptions(successfulHistory: $successful, failedHistory: $failed));

        self::assertSame(0, $this->tick($shell, self::at('2026-02-01T02:17:05Z')));
        self::assertSame($kept, $this->historyLogs());
    }

    #[DataProvider('provideShells')]
    public function testAFailedRunIsKeptInTheFailedHistory(string $shell): void
    {
        $this->configure('17 2 * * *', null, ['completions' => 3]);

        self::assertSame(3, $this->tick($shell, self::at('2026-02-01T02:17:05Z'), ['FAKE_EXIT_CODE' => '3']));
        self::assertSame(
            [self::STATE_LOCK, self::STATE_UNLOCK, ...self::callsOfACompletion(1)],
            $this->calls(),
        );
        self::assertSame(['20260201T021700Z.failed.log'], $this->historyLogs());
        self::assertSame(['20260201T021700Z: started', '20260201T021700Z: failed, exit code 3'], $this->events());
    }

    #[DataProvider('provideShells')]
    public function testTheSuccessExitCodesOfTheJob(string $shell): void
    {
        $this->configure('17 2 * * *', null, ['completions' => 3, 'success_exit_codes' => [0, 3]]);

        //A success exit code continues the completions, the run exits with the code of the last one
        self::assertSame(
            4,
            $this->tick($shell, self::at('2026-02-01T02:17:05Z'), ['FAKE_EXIT_CODES' => 'cid1=3 cid2=4']),
        );
        self::assertSame(
            [
                self::STATE_LOCK,
                self::STATE_UNLOCK,
                ...self::callsOfACompletion(1),
                ...self::callsOfACompletion(2),
            ],
            $this->calls(),
        );
        self::assertSame(['20260201T021700Z.failed.log'], $this->historyLogs());

        self::assertSame(
            3,
            $this->tick($shell, self::at('2026-02-02T02:17:05Z'), ['FAKE_EXIT_CODES' => 'cid3=3 cid4=0 cid5=3']),
        );
        self::assertSame(['20260201T021700Z.failed.log', '20260202T021700Z.succeeded.log'], $this->historyLogs());
        self::assertSame(
            [
                '20260201T021700Z: started',
                '20260201T021700Z: failed, exit code 4',
                '20260202T021700Z: started',
                '20260202T021700Z: succeeded, exit code 3',
            ],
            $this->events(),
        );
    }

    #[DataProvider('provideShells')]
    public function testTheTimeLimitStopsTheContainers(string $shell): void
    {
        $this->configure('17 2 * * *', null, ['completions' => 2, 'time_limit' => 30]);

        self::assertSame(124, $this->tick($shell, self::at('2026-02-01T02:17:05Z'), ['FAKE_TIMEOUT' => '1']));
        self::assertSame(
            [
                self::STATE_LOCK,
                self::STATE_UNLOCK,
                'sh jobs/east-paas-pod.sh prj scheduled 30 job-backup',
                'docker compose -p prj --profile scheduled run -d --no-deps job-backup',
                'timeout 30 docker wait cid1',
                'docker stop cid1',
                'docker logs cid1',
                'docker rm -f cid1',
            ],
            $this->calls(),
        );
        self::assertSame(
            "output of cid1\neast-paas: the container is stopped, the time limit of 30 seconds is reached\n",
            file_get_contents($this->state('20260201T021700Z.failed.log')),
        );

        self::assertSame(0, $this->tick($shell, self::at('2026-02-02T02:17:05Z')));
        self::assertSame(['20260201T021700Z.failed.log', '20260202T021700Z.succeeded.log'], $this->historyLogs());
    }

    /**
     * @return iterable<string, array{string, string, array<string, string>, string}>
     */
    public static function provideMissingTools(): iterable
    {
        foreach (self::provideShells() as $name => [$shell]) {
            yield 'flock with ' . $name => [$shell, 'flock', [], 'flock (util-linux) is missing'];
            yield 'date -d with ' . $name => [
                $shell,
                '',
                ['FAKE_DATE_BSD' => '1'],
                'date -d @<seconds> (GNU coreutils or busybox) is missing',
            ];
            yield 'timeout with ' . $name => [
                $shell,
                'timeout',
                [],
                'timeout (coreutils) is missing, required by the time limit',
            ];
        }
    }

    /**
     * @param array<string, string> $env
     */
    #[DataProvider('provideMissingTools')]
    public function testAMissingToolFails(string $shell, string $tool, array $env, string $event): void
    {
        if ('' !== $tool) {
            unlink($this->dir . '/bin/' . $tool);
        }

        $this->configure('17 2 * * *', null, ['time_limit' => 30]);

        self::assertSame(2, $this->tick($shell, self::at('2026-02-01T02:17:05Z'), $env));
        self::assertSame([$event], $this->events());
        self::assertSame([], $this->calls());
        self::assertNull($this->readState('seen'));
    }

    #[DataProvider('provideShells')]
    public function testTimeoutIsNotRequiredWithoutTimeLimit(string $shell): void
    {
        unlink($this->dir . '/bin/timeout');
        $this->configure('17 2 * * *');

        self::assertSame(0, $this->tick($shell, self::at('2026-02-01T02:17:05Z')));
        self::assertSame(['20260201T021700Z.succeeded.log'], $this->historyLogs());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideInvalidSeen(): iterable
    {
        foreach (self::provideShells() as $name => [$shell]) {
            yield 'not a number with ' . $name => [$shell, 'abc'];
            yield 'empty with ' . $name => [$shell, ''];
            yield 'negative with ' . $name => [$shell, '-60'];
            yield 'leading zero with ' . $name => [$shell, '0' . self::at('2026-02-01T02:15:00Z')];
            yield 'after the current minute with ' . $name => [$shell, (string) self::at('2026-02-01T03:17:00Z')];
        }
    }

    #[DataProvider('provideInvalidSeen')]
    public function testAnInvalidLastMinuteExaminedIsIgnored(string $shell, string $seen): void
    {
        $this->configure('16 2 * * *');
        $this->writeState('seen', $seen);
        $minute = self::at('2026-02-01T02:17:00Z');

        self::assertSame(0, $this->tick($shell, $minute + 5));
        self::assertSame([(string) $minute], $this->scannedMinutes());
        self::assertSame((string) $minute, $this->readState('seen'));
        self::assertSame([], $this->historyLogs());
    }

    #[DataProvider('provideShells')]
    public function testAnInvalidPendingTimeIsIgnored(string $shell): void
    {
        $this->configure('16 2 * * *', new ScheduleOptions(concurrency: ConcurrencyPolicy::Forbid));
        $minute = self::at('2026-02-01T02:17:00Z');

        foreach (['abc', (string) ($minute + 60), '0' . ($minute - 60)] as $pending) {
            $this->writeState('seen', $minute - 60);
            $this->writeState('pending', $pending);

            self::assertSame(0, $this->tick($shell, $minute + 5));
            self::assertSame([], $this->historyLogs());
        }

        self::assertSame([self::STATE_LOCK, self::STATE_LOCK, self::STATE_LOCK], $this->calls());
    }

    #[DataProvider('provideShells')]
    public function testTheEventsLogKeepsItsLast100Lines(string $shell): void
    {
        $this->configure('17 2 * * *');
        $old = '';
        foreach (range(1, 150) as $line) {
            $old .= "2026-01-01T00:00:00Z old event $line\n";
        }

        file_put_contents($this->state('events.log'), $old);
        $inode = fileinode($this->state('events.log'));

        self::assertSame(0, $this->tick($shell, self::at('2026-02-01T02:17:05Z')));

        //Rewritten in place: the events of the tick, appended by the entry, are not lost
        $events = $this->events();
        self::assertSame($inode, fileinode($this->state('events.log')));
        self::assertCount(102, $events);
        self::assertSame('old event 51', $events[0]);
        self::assertSame(
            ['20260201T021700Z: started', '20260201T021700Z: succeeded, exit code 0'],
            array_slice($events, 100),
        );
        self::assertFileDoesNotExist($this->state('events.tmp'));
    }

    #[DataProvider('provideShells')]
    public function testTheLocalTimeOfTheZoneOfTheJob(string $shell): void
    {
        //03:17 in Paris, UTC+1 in winter
        $this->configure('17 3 * * *', new ScheduleOptions(timeZone: 'Europe/Paris'));
        $scheduled = self::at('2026-02-01T02:17:00Z');
        $this->writeState('seen', $scheduled - 60);

        self::assertSame(0, $this->tick($shell, $scheduled + 5));
        self::assertSame(['20260201T021700Z.succeeded.log'], $this->historyLogs());
        self::assertSame(
            ['[TZ=Europe/Paris] -d @' . $scheduled . ' ' . self::SCAN_FORMAT],
            $this->scanCalls(),
        );
    }

    #[DataProvider('provideShells')]
    public function testARepeatedLocalTimeRunsTwice(string $shell): void
    {
        //On the 25th October 2026, 02:30 in Paris is at 00:30 UTC (summer time), then at 01:30 UTC
        $this->configure('30 2 * * *', new ScheduleOptions(timeZone: 'Europe/Paris'));
        $this->writeState('seen', self::at('2026-10-25T00:29:00Z'));

        self::assertSame(0, $this->tick($shell, self::at('2026-10-25T00:30:05Z')));
        self::assertSame(0, $this->tick($shell, self::at('2026-10-25T01:30:05Z')));
        self::assertSame(
            ['20261025T003000Z.succeeded.log', '20261025T013000Z.succeeded.log'],
            $this->historyLogs(),
        );
    }

    #[DataProvider('provideShells')]
    public function testASkippedLocalTimeNeverRuns(string $shell): void
    {
        //On the 29th March 2026, Paris goes from 02:00 to 03:00 at 01:00 UTC
        $this->configure('30 2 * * *', new ScheduleOptions(timeZone: 'Europe/Paris'));
        $this->writeState('seen', self::at('2026-03-29T00:29:00Z'));

        self::assertSame(0, $this->tick($shell, self::at('2026-03-29T01:31:05Z')));
        self::assertSame([], $this->historyLogs());
        self::assertCount(62, $this->scannedMinutes());
    }

    /**
     * @return iterable<string, array{string, ConcurrencyPolicy, list<string>, string}>
     */
    public static function provideRealLocks(): iterable
    {
        foreach (self::provideShells() as $name => [$shell]) {
            yield 'forbid with ' . $name => [$shell, ConcurrencyPolicy::Forbid, [self::ANCHOR], 'busy'];
            yield 'replace with ' . $name => [$shell, ConcurrencyPolicy::Replace, [self::ANCHOR], 'busy'];
            yield 'allow and a pod of several services with ' . $name => [
                $shell,
                ConcurrencyPolicy::Allow,
                [self::ANCHOR, 'job-backup-proxy'],
                'busy',
            ];
            yield 'allow and a pod of one service with ' . $name => [
                $shell,
                ConcurrencyPolicy::Allow,
                [self::ANCHOR],
                'free',
            ];
        }
    }

    /**
     * @param list<string> $services
     */
    #[DataProvider('provideRealLocks')]
    public function testTheLocksHeldWithTheRealFlock(
        string $shell,
        ConcurrencyPolicy $concurrency,
        array $services,
        string $runLock,
    ): void {
        $this->useTheRealFlock();
        $this->configure('17 2 * * *', new ScheduleOptions(concurrency: $concurrency), [], $services);

        self::assertSame(
            0,
            $this->tick($shell, self::at('2026-02-01T02:17:05Z'), ['FAKE_PROBE_LOCKS' => 'state.lock run.lock']),
        );
        self::assertSame(['20260201T021700Z.succeeded.log'], $this->historyLogs());

        //The state is examined under its lock, the run holds its lock until its end but not the lock of the state
        self::assertSame(
            ['date: state.lock busy', 'date: run.lock free', 'wait: state.lock free', 'wait: run.lock ' . $runLock],
            array_values((array) preg_grep('#^(date|wait): #', $this->calls())),
        );
    }

    #[DataProvider('provideShells')]
    public function testForbidKeepsTheTimePendingWhileThePreviousRunHoldsTheRealLock(string $shell): void
    {
        $this->useTheRealFlock();
        $this->configure('17 2 * * *', new ScheduleOptions(concurrency: ConcurrencyPolicy::Forbid));
        $scheduled = self::at('2026-02-01T02:17:00Z');
        $this->writeState('seen', $scheduled - 60);

        $previous = $this->holdLock('run.lock');
        self::assertSame(0, $this->tick($shell, $scheduled + 5));
        self::assertSame((string) $scheduled, $this->readState('pending'));
        self::assertNull($this->readState('last'));
        self::assertSame([], $this->calls());

        self::releaseLock($previous);
        self::assertSame(0, $this->tick($shell, $scheduled + 65));
        self::assertSame(self::callsOfACompletion(1), $this->calls());
        self::assertSame(['20260201T021700Z.succeeded.log'], $this->historyLogs());
        self::assertNull($this->readState('pending'));
        self::assertSame((string) $scheduled, $this->readState('last'));
    }

    #[DataProvider('provideShells')]
    public function testATickWaitsForTheRealLockOfTheState(string $shell): void
    {
        $this->useTheRealFlock();
        $this->configure('17 2 * * *');

        $other = $this->holdLock('state.lock');
        $tick = $this->tickProcess($shell, self::at('2026-02-01T02:17:05Z'));
        $tick->start();
        usleep(300000);

        self::assertTrue($tick->isRunning());
        self::assertNull($this->readState('seen'));
        self::assertSame([], $this->calls());

        self::releaseLock($other);
        self::assertSame(0, $tick->wait());
        self::assertSame(self::callsOfACompletion(1), $this->calls());
        self::assertSame(['20260201T021700Z.succeeded.log'], $this->historyLogs());
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function provideRealDates(): iterable
    {
        foreach (self::provideShells() as $name => [$shell]) {
            yield 'zone of the host with ' . $name => [$shell, null];
            yield 'UTC with ' . $name => [$shell, 'UTC'];
        }
    }

    #[DataProvider('provideRealDates')]
    public function testWithTheRealDate(string $shell, ?string $zone): void
    {
        $date = new Process([self::lookup('date'), '-d', '@0', '+%s']);
        $date->run();
        if ('0' !== trim($date->getOutput())) {
            self::markTestSkipped('The date of this host does not support `-d @<seconds>` (BSD)');
        }

        unlink($this->dir . '/bin/date');
        symlink(self::lookup('date'), $this->dir . '/bin/date');

        $this->configure('* * * * *', new ScheduleOptions(timeZone: $zone));
        $before = time();
        self::assertSame(0, $this->tick($shell, $before));
        $after = time();

        $seen = (int) $this->readState('seen');
        self::assertContains($seen, [$before - $before % 60, $after - $after % 60]);
        self::assertSame((string) $seen, $this->readState('last'));
        self::assertSame(
            [(new DateTimeImmutable('@' . $seen))->format('Ymd\THis\Z') . '.succeeded.log'],
            $this->historyLogs(),
        );
        self::assertCount(2, $this->events());
    }
}
