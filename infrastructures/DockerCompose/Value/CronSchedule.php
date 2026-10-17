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

namespace Teknoo\East\Paas\Infrastructures\DockerCompose\Value;

use Teknoo\East\Paas\Infrastructures\DockerCompose\Exception\InvalidConfigurationException;

use function array_map;
use function array_values;
use function count;
use function ctype_digit;
use function explode;
use function implode;
use function ksort;
use function preg_match;
use function preg_split;
use function sprintf;
use function str_starts_with;
use function strtolower;

use const PREG_SPLIT_NO_EMPTY;
use const PREG_UNMATCHED_AS_NULL;

/**
 * Immutable translation of the schedule of a scheduled job (the syntax of Kubernetes, read by robfig/cron v3) into the
 * regular expression `pattern`, matched by the runner with `grep -E` (POSIX ERE, PCRE compatible) against the minute
 * formatted by `date '+%M %H %d %m %w'` in the time zone of the job, else of the host.
 *
 * Like robfig/cron, when the day of month or the day of week is a star field (an item `*` or `?` without a step above
 * 1), both must match, else either matches.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
final readonly class CronSchedule
{
    private const string EVERY_PREFIX = '@every ';

    private const array DESCRIPTORS = [
        '@yearly' => '0 0 1 1 *',
        '@annually' => '0 0 1 1 *',
        '@monthly' => '0 0 1 * *',
        '@weekly' => '0 0 * * 0',
        '@daily' => '0 0 * * *',
        '@midnight' => '0 0 * * *',
        '@hourly' => '0 * * * *',
    ];

    private const array MONTHS = [
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
    ];

    private const array WEEKDAYS = [
        'sun' => 0,
        'mon' => 1,
        'tue' => 2,
        'wed' => 3,
        'thu' => 4,
        'fri' => 5,
        'sat' => 6,
    ];

    /**
     * Formats of the fields in the output of `date '+%M %H %d %m %w'`
     */
    private const array FORMATS = ['%02d', '%02d', '%02d', '%02d', '%d'];

    private const array ANY = ['[0-9]{2}', '[0-9]{2}', '[0-9]{2}', '[0-9]{2}', '[0-6]'];

    private const int DAY = 2;

    private const int WEEKDAY = 4;

    private function __construct(
        public ?string $timeZone,
        public string $pattern,
    ) {
    }

    private static function invalid(string $schedule): InvalidConfigurationException
    {
        return new InvalidConfigurationException(
            "The schedule `$schedule` is not a valid schedule of a scheduled job",
        );
    }

    /**
     * @param array<string, int> $names
     */
    private static function parseValue(string $value, array $names, string $schedule): int
    {
        $lower = strtolower($value);
        if (isset($names[$lower])) {
            return $names[$lower];
        }

        if (!ctype_digit($value)) {
            throw self::invalid($schedule);
        }

        return (int) $value;
    }

    /**
     * Expand a field like robfig/cron: `N/step` means `N-max/step`.
     *
     * @param array<string, int> $names
     * @return array{values: list<int>, star: bool}
     */
    private static function expandField(string $field, int $min, int $max, array $names, string $schedule): array
    {
        $values = [];
        $star = false;
        foreach (explode(',', $field) as $item) {
            if (
                1 !== preg_match(
                    '#^(?:([*?])|([0-9a-z]+)(?:-([0-9a-z]+))?)(?:/([0-9]+))?$#iD',
                    $item,
                    $matches,
                    PREG_UNMATCHED_AS_NULL,
                )
            ) {
                throw self::invalid($schedule);
            }

            $step = 1;
            if (isset($matches[4])) {
                $step = (int) $matches[4];
            }

            if (isset($matches[1])) {
                $start = $min;
                $end = $max;
                $star = $star || 1 === $step;
            } else {
                $start = self::parseValue((string) $matches[2], $names, $schedule);
                $end = $start;
                if (isset($matches[3])) {
                    $end = self::parseValue($matches[3], $names, $schedule);
                } elseif (isset($matches[4])) {
                    $end = $max;
                }
            }

            if ($start < $min || $end > $max || $start > $end || $step < 1) {
                throw self::invalid($schedule);
            }

            for ($value = $start; $value <= $end; $value += $step) {
                $values[$value] = $value;
            }
        }

        ksort($values);

        return [
            'values' => array_values($values),
            'star' => $star,
        ];
    }

    /**
     * @param array{values: list<int>, star: bool} $field
     */
    private static function patternField(array $field, int $index): string
    {
        if ($field['star']) {
            return self::ANY[$index];
        }

        $format = self::FORMATS[$index];
        $values = array_map(
            static fn (int $value): string => sprintf($format, $value),
            $field['values'],
        );

        return '(' . implode('|', $values) . ')';
    }

    /**
     * @param list<array{values: list<int>, star: bool}> $fields
     */
    private static function buildPattern(array $fields): string
    {
        $parts = [];
        foreach ($fields as $index => $field) {
            $parts[$index] = self::patternField($field, $index);
        }

        if ($fields[self::DAY]['star'] || $fields[self::WEEKDAY]['star']) {
            return '^' . implode(' ', $parts) . '$';
        }

        //Both days are restricted: the day of month or the day of week must match
        $onDay = $parts;
        $onDay[self::WEEKDAY] = self::ANY[self::WEEKDAY];

        $onWeekday = $parts;
        $onWeekday[self::DAY] = self::ANY[self::DAY];

        return '^' . implode(' ', $onDay) . '$|^' . implode(' ', $onWeekday) . '$';
    }

    /**
     * Null for `@every <duration>`, not a list of minutes. A schedule refused by Kubernetes but not by the JobCompiler,
     * like a reversed range `5-1`, throws an InvalidConfigurationException.
     */
    public static function fromSchedule(string $schedule, ?string $timeZone = null): ?self
    {
        if (str_starts_with($schedule, self::EVERY_PREFIX)) {
            return null;
        }

        $fields = preg_split('#\s+#', self::DESCRIPTORS[$schedule] ?? $schedule, -1, PREG_SPLIT_NO_EMPTY);
        if (false === $fields || 5 !== count($fields)) {
            throw self::invalid($schedule);
        }

        $expanded = [
            self::expandField($fields[0], 0, 59, [], $schedule),
            self::expandField($fields[1], 0, 23, [], $schedule),
            self::expandField($fields[2], 1, 31, [], $schedule),
            self::expandField($fields[3], 1, 12, self::MONTHS, $schedule),
            self::expandField($fields[4], 0, 6, self::WEEKDAYS, $schedule),
        ];

        if ('' === $timeZone) {
            $timeZone = null;
        }

        return new self(
            timeZone: $timeZone,
            pattern: self::buildPattern($expanded),
        );
    }
}
