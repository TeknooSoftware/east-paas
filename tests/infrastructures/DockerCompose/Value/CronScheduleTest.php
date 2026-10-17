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

namespace Teknoo\Tests\East\Paas\Infrastructures\DockerCompose\Value;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Exception\InvalidConfigurationException;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\CronSchedule;

use function array_map;
use function array_values;
use function count;
use function date_default_timezone_get;
use function explode;
use function implode;
use function preg_grep;
use function preg_match;
use function preg_split;
use function strtolower;
use function trim;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(CronSchedule::class)]
class CronScheduleTest extends TestCase
{
    /**
     * One schedule per syntax and per case of the rule on the days
     */
    private const array SWEPT_SCHEDULES = [
        '*/15 */3 * * *',
        '30 2 * * *',
        '0 9 1,15 * MON-FRI',
        '0 0 */2 * *',
        '5/20 8-18/5 ? JAN-MAR,dec sun',
        '0 12 1-31 * 1',
        '10 4 13 * fri',
        '45 23 * * 0-6',
        '@weekly',
        '* * * * *',
    ];

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function providePatterns(): iterable
    {
        $any = '[0-9]{2} [0-9]{2} [0-9]{2} [0-9]{2} [0-6]';

        yield '@yearly' => ['@yearly', '^(00) (00) (01) (01) [0-6]$'];
        yield '@annually' => ['@annually', '^(00) (00) (01) (01) [0-6]$'];
        yield '@monthly' => ['@monthly', '^(00) (00) (01) [0-9]{2} [0-6]$'];
        yield '@weekly' => ['@weekly', '^(00) (00) [0-9]{2} [0-9]{2} (0)$'];
        yield '@daily' => ['@daily', '^(00) (00) [0-9]{2} [0-9]{2} [0-6]$'];
        yield '@midnight' => ['@midnight', '^(00) (00) [0-9]{2} [0-9]{2} [0-6]$'];
        yield '@hourly' => ['@hourly', '^(00) [0-9]{2} [0-9]{2} [0-9]{2} [0-6]$'];
        yield 'every minute' => ['* * * * *', '^' . $any . '$'];
        yield 'question marks' => ['? ? ? ? ?', '^' . $any . '$'];
        yield 'star with a step of 1' => ['*/1 ?/1 * * *', '^' . $any . '$'];
        yield 'star in a list' => ['*,5 * * * *', '^' . $any . '$'];
        yield 'values' => ['17 3 * * *', '^(17) (03) [0-9]{2} [0-9]{2} [0-6]$'];
        yield 'leading zeros' => ['05 03 01 02 00', '^(05) (03) (01) (02) [0-6]$|^(05) (03) [0-9]{2} (02) (0)$'];
        yield 'steps' => ['*/15 */3 * * *', '^(00|15|30|45) (00|03|06|09|12|15|18|21) [0-9]{2} [0-9]{2} [0-6]$'];
        yield 'step on the days' => [
            '0 0 */10 */4 */3',
            '^(00) (00) (01|11|21|31) (01|05|09) [0-6]$|^(00) (00) [0-9]{2} (01|05|09) (0|3|6)$',
        ];
        yield 'N/step' => [
            '5/20 22/1 25/3 11/2 5/1',
            '^(05|25|45) (22|23) (25|28|31) (11) [0-6]$|^(05|25|45) (22|23) [0-9]{2} (11) (5|6)$',
        ];
        yield 'ranges with a step' => [
            '10-30/10 8-18/5 1-10/3 * *',
            '^(10|20|30) (08|13|18) (01|04|07|10) [0-9]{2} [0-6]$',
        ];
        yield 'lists' => ['30,0,15 12,0 15,1 * *', '^(00|15|30) (00|12) (01|15) [0-9]{2} [0-6]$'];
        yield 'duplicates in a list' => ['0,0-2,1 * * * *', '^(00|01|02) [0-9]{2} [0-9]{2} [0-9]{2} [0-6]$'];
        yield 'day of month restricted, day of week star' => [
            '0 0 */10 * ?',
            '^(00) (00) (01|11|21|31) [0-9]{2} [0-6]$',
        ];
        yield 'day of week restricted, day of month star' => [
            '0 0 * JAN,dec sun',
            '^(00) (00) [0-9]{2} (01|12) (0)$',
        ];
        yield 'both days restricted' => [
            '0 9 1,15 * MON-FRI',
            '^(00) (09) (01|15) [0-9]{2} [0-6]$|^(00) (09) [0-9]{2} [0-9]{2} (1|2|3|4|5)$',
        ];
        yield 'explicit full range of days is not a star' => [
            '0 12 1-7 * 1',
            '^(00) (12) (01|02|03|04|05|06|07) [0-9]{2} [0-6]$|^(00) (12) [0-9]{2} [0-9]{2} (1)$',
        ];
        yield 'step on the day of week is not a star' => [
            '0 0 1 * */2',
            '^(00) (00) (01) [0-9]{2} [0-6]$|^(00) (00) [0-9]{2} [0-9]{2} (0|2|4|6)$',
        ];
        yield 'names of days' => ['0 0 * * MON-FRI', '^(00) (00) [0-9]{2} [0-9]{2} (1|2|3|4|5)$'];
        yield 'names of days in a list' => ['0 0 * * sun,Wed,SAT', '^(00) (00) [0-9]{2} [0-9]{2} (0|3|6)$'];
        yield 'names of months' => ['0 0 1 JAN-MAR,dec *', '^(00) (00) (01) (01|02|03|12) [0-6]$'];
        yield 'names with a step' => [
            '0 0 1 Jan-Jun/2 mon/2',
            '^(00) (00) (01) (01|03|05) [0-6]$|^(00) (00) [0-9]{2} (01|03|05) (1|3|5)$',
        ];
        yield 'spaces' => [
            "  0\t9   1,15 * \n MON-FRI ",
            '^(00) (09) (01|15) [0-9]{2} [0-6]$|^(00) (09) [0-9]{2} [0-9]{2} (1|2|3|4|5)$',
        ];
    }

    #[DataProvider('providePatterns')]
    public function testFromScheduleWithoutTimeZone(string $schedule, string $pattern): void
    {
        $cron = CronSchedule::fromSchedule($schedule);

        self::assertInstanceOf(CronSchedule::class, $cron);
        self::assertNull($cron->timeZone);
        self::assertSame($pattern, $cron->pattern);
    }

    #[DataProvider('providePatterns')]
    public function testFromScheduleWithATimeZone(string $schedule, string $pattern): void
    {
        $cron = CronSchedule::fromSchedule($schedule, 'Europe/Paris');

        self::assertInstanceOf(CronSchedule::class, $cron);
        self::assertSame('Europe/Paris', $cron->timeZone);
        self::assertSame($pattern, $cron->pattern);
    }

    public function testFromScheduleWithAnEmptyTimeZoneUsesTheHostZone(): void
    {
        $cron = CronSchedule::fromSchedule('0 */3 * * *', '');

        self::assertInstanceOf(CronSchedule::class, $cron);
        self::assertNull($cron->timeZone);
        self::assertSame('^(00) (00|03|06|09|12|15|18|21) [0-9]{2} [0-9]{2} [0-6]$', $cron->pattern);
    }

    public function testFromScheduleEveryIsNotSupported(): void
    {
        self::assertNull(CronSchedule::fromSchedule('@every 1h'));
        self::assertNull(CronSchedule::fromSchedule('@every 1h30m', 'Europe/Paris'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidSchedules(): iterable
    {
        yield 'empty' => [''];
        yield 'four fields' => ['* * * *'];
        yield 'six fields' => ['0 * * * * *'];
        yield 'unknown descriptor' => ['@reboot'];
        yield 'descriptor in uppercase' => ['@DAILY'];
        yield 'every without duration' => ['@every'];
        yield 'minute out of bounds' => ['60 * * * *'];
        yield 'hour out of bounds' => ['0 24 * * *'];
        yield 'day of month 0' => ['0 0 0 * *'];
        yield 'day of month out of bounds' => ['0 0 32 * *'];
        yield 'month 0' => ['0 0 * 0 *'];
        yield 'month out of bounds' => ['0 0 * 13 *'];
        yield 'day of week 7' => ['0 0 * * 7'];
        yield 'reversed range' => ['5-1 * * * *'];
        yield 'step 0' => ['*/0 * * * *'];
        yield 'step 0 on a value' => ['5/0 * * * *'];
        yield 'name in the minutes' => ['jan * * * *'];
        yield 'day name in the months' => ['0 0 * mon *'];
        yield 'month name in the days of week' => ['0 0 * * jan'];
        yield 'unknown name' => ['0 0 * * monday'];
        yield 'double range' => ['1-2-3 * * * *'];
        yield 'double step' => ['*/2/3 * * * *'];
        yield 'negative value' => ['-1 * * * *'];
        yield 'empty item' => ['1,,2 * * * *'];
        yield 'time zone in the schedule' => ['TZ=UTC 0 0 * * *'];
        yield 'percent' => ['0 0 * * %'];
    }

    #[DataProvider('provideInvalidSchedules')]
    public function testFromScheduleRefusesAnInvalidSchedule(string $schedule): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage("The schedule `$schedule` is not a valid schedule of a scheduled job");

        CronSchedule::fromSchedule($schedule, 'Europe/Paris');
    }

    /**
     * Independent reference parser of robfig/cron v3, the parser of Kubernetes
     *
     * @return list<array{bits: int, star: bool}>
     */
    private static function referenceParse(string $schedule): array
    {
        $descriptors = [
            '@yearly' => '0 0 1 1 *',
            '@annually' => '0 0 1 1 *',
            '@monthly' => '0 0 1 * *',
            '@weekly' => '0 0 * * 0',
            '@daily' => '0 0 * * *',
            '@midnight' => '0 0 * * *',
            '@hourly' => '0 * * * *',
        ];

        $months = [
            'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
            'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
        ];
        $days = ['sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6];
        $bounds = [[0, 59, []], [0, 23, []], [1, 31, []], [1, 12, $months], [0, 6, $days]];

        $spec = $descriptors[$schedule] ?? $schedule;
        $parsed = [];
        foreach ((array) preg_split('/\s+/', trim($spec)) as $index => $field) {
            [$min, $max, $names] = $bounds[$index];
            $parse = static function (string $value) use ($names): int {
                return $names[strtolower($value)] ?? (int) $value;
            };

            $bits = 0;
            $star = false;
            foreach (explode(',', (string) $field) as $expression) {
                $rangeAndStep = explode('/', $expression);
                $lowAndHigh = explode('-', $rangeAndStep[0]);
                $extraStar = false;
                if ('*' === $lowAndHigh[0] || '?' === $lowAndHigh[0]) {
                    $start = $min;
                    $end = $max;
                    $extraStar = true;
                } else {
                    $start = $parse($lowAndHigh[0]);
                    $end = $start;
                    if (2 === count($lowAndHigh)) {
                        $end = $parse($lowAndHigh[1]);
                    }
                }

                $step = 1;
                if (2 === count($rangeAndStep)) {
                    $step = (int) $rangeAndStep[1];
                    if (1 === count($lowAndHigh)) {
                        $end = $max;
                    }

                    if ($step > 1) {
                        $extraStar = false;
                    }
                }

                for ($value = $start; $value <= $end; $value += $step) {
                    $bits |= 1 << $value;
                }

                $star = $star || $extraStar;
            }

            $parsed[] = ['bits' => $bits, 'star' => $star];
        }

        return $parsed;
    }

    /**
     * Matching of robfig/cron v3, see its `dayMatches()`
     *
     * @param list<array{bits: int, star: bool}> $spec
     * @param array{int, int, int, int, int} $time minute, hour, day of month, month, day of week
     */
    private static function referenceMatches(array $spec, array $time): bool
    {
        foreach ([0, 1, 3] as $index) {
            if (0 === ($spec[$index]['bits'] & (1 << $time[$index]))) {
                return false;
            }
        }

        $dayMatch = 0 !== ($spec[2]['bits'] & (1 << $time[2]));
        $weekdayMatch = 0 !== ($spec[4]['bits'] & (1 << $time[4]));
        if ($spec[2]['star'] || $spec[4]['star']) {
            return $dayMatch && $weekdayMatch;
        }

        return $dayMatch || $weekdayMatch;
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function provideTimeZones(): iterable
    {
        yield 'zone of the host' => [null];
        yield 'UTC' => ['UTC'];
        yield 'Europe/Paris' => ['Europe/Paris'];
        yield 'Asia/Kolkata' => ['Asia/Kolkata'];
        yield 'America/New_York' => ['America/New_York'];
    }

    /**
     * Every 11 minutes over 400 days, across the DST changes of 2026: 11 is coprime with the 10080 minutes of a week,
     * every minute of every day of week is reached.
     */
    #[DataProvider('provideTimeZones')]
    public function testPatternMatchesLikeKubernetesOverASweepOfInstants(?string $zone): void
    {
        $crons = [];
        $references = [];
        $matchesCount = [];
        foreach (self::SWEPT_SCHEDULES as $schedule) {
            $cron = CronSchedule::fromSchedule($schedule, $zone);
            self::assertInstanceOf(CronSchedule::class, $cron);
            $crons[$schedule] = '#' . $cron->pattern . '#D';
            $references[$schedule] = self::referenceParse($schedule);
            $matchesCount[$schedule] = 0;
        }

        $zone ??= date_default_timezone_get();
        $date = new DateTime('now', new DateTimeZone($zone));
        $start = (new DateTimeImmutable('2026-01-01T00:00:00Z'))->getTimestamp();
        $end = $start + 400 * 86400;
        $mismatches = [];
        for ($timestamp = $start; $timestamp < $end; $timestamp += 660) {
            $local = $date->setTimestamp($timestamp)->format('i H d m w');
            $time = array_values(array_map('intval', explode(' ', $local)));

            foreach ($crons as $schedule => $pattern) {
                $expected = self::referenceMatches($references[$schedule], $time);
                if ($expected) {
                    ++$matchesCount[$schedule];
                }

                if ($expected !== (1 === preg_match($pattern, $local))) {
                    $mismatches[] = "$schedule at $local ($zone)";
                }
            }
        }

        self::assertSame([], $mismatches);
        foreach ($matchesCount as $schedule => $count) {
            self::assertGreaterThan(0, $count, "The schedule `$schedule` has never been matched");
        }
    }

    private static function countMatchesOfTheDay(string $schedule, string $zone, string $day): int
    {
        $cron = CronSchedule::fromSchedule($schedule, $zone);
        self::assertInstanceOf(CronSchedule::class, $cron);

        $date = new DateTime('now', new DateTimeZone($zone));
        $start = (new DateTimeImmutable($day . 'T00:00:00', new DateTimeZone($zone)))->getTimestamp();
        $end = (new DateTimeImmutable($day . 'T00:00:00', new DateTimeZone($zone)))
            ->modify('+1 day')
            ->getTimestamp();

        $count = 0;
        for ($timestamp = $start; $timestamp < $end; $timestamp += 60) {
            if (1 === preg_match('#' . $cron->pattern . '#D', $date->setTimestamp($timestamp)->format('i H d m w'))) {
                ++$count;
            }
        }

        return $count;
    }

    public function testPatternOnTheDaysOfDstChanges(): void
    {
        //A local time skipped by the change to the summer time never runs
        self::assertSame(0, self::countMatchesOfTheDay('30 2 * * *', 'Europe/Paris', '2026-03-29'));
        self::assertSame(0, self::countMatchesOfTheDay('30 2 * * *', 'America/New_York', '2026-03-08'));
        self::assertSame(1, self::countMatchesOfTheDay('30 3 * * *', 'Europe/Paris', '2026-03-29'));

        //A local time repeated by the change to the winter time runs twice
        self::assertSame(2, self::countMatchesOfTheDay('30 2 * * *', 'Europe/Paris', '2026-10-25'));
        self::assertSame(2, self::countMatchesOfTheDay('30 1 * * *', 'America/New_York', '2026-11-01'));
        self::assertSame(1, self::countMatchesOfTheDay('30 3 * * *', 'Europe/Paris', '2026-10-25'));

        self::assertSame(1, self::countMatchesOfTheDay('30 2 * * *', 'Europe/Paris', '2026-03-28'));
        self::assertSame(24, self::countMatchesOfTheDay('0 * * * *', 'Asia/Kolkata', '2026-03-29'));
    }

    /**
     * The pattern is matched on the host by `grep -E` (POSIX ERE): it must select the same lines as PCRE.
     */
    public function testPatternIsAPosixExtendedRegularExpression(): void
    {
        $grep = new Process(['grep', '--version']);
        $grep->run();
        if (!$grep->isSuccessful()) {
            self::markTestSkipped('grep is not available');
        }

        $date = new DateTime('now', new DateTimeZone('UTC'));
        $start = (new DateTimeImmutable('2026-01-01T00:00:00Z'))->getTimestamp();
        $lines = [];
        for ($timestamp = $start; $timestamp < $start + 400 * 86400; $timestamp += 3 * 660) {
            $lines[] = $date->setTimestamp($timestamp)->format('i H d m w');
        }

        $input = implode("\n", $lines) . "\n";
        foreach (self::SWEPT_SCHEDULES as $schedule) {
            $cron = CronSchedule::fromSchedule($schedule, 'UTC');
            self::assertInstanceOf(CronSchedule::class, $cron);

            $process = new Process(['grep', '-E', '-e', (string) $cron->pattern]);
            $process->setInput($input);
            $process->run();

            $selected = (string) $process->getOutput();
            $expected = array_values((array) preg_grep('#' . $cron->pattern . '#D', $lines));
            $expectedOutput = '';
            if (!empty($expected)) {
                $expectedOutput = implode("\n", $expected) . "\n";
            }

            self::assertSame($expectedOutput, $selected, "grep -E differs from PCRE for `$schedule`");
        }
    }
}
