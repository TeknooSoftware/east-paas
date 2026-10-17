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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\JobRunSettings;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(JobRunSettings::class)]
class JobRunSettingsTest extends TestCase
{
    /**
     * @return iterable<string, array{array<mixed>, int, int, list<int>}>
     */
    public static function provideMetas(): iterable
    {
        yield 'empty' => [[], 1, 0, [0]];
        yield 'defined' => [
            ['completions' => 3, 'time_limit' => 600, 'success_exit_codes' => [0, 3]],
            3,
            600,
            [0, 3],
        ];
        yield 'numeric strings' => [['completions' => '2', 'time_limit' => '30'], 2, 30, [0]];
        yield 'at least one completion' => [['completions' => 0], 1, 0, [0]];
        yield 'not numeric' => [['completions' => 'two', 'time_limit' => 'never'], 1, 0, [0]];
        yield 'codes deduplicated and converted' => [['success_exit_codes' => ['3', 3, 0]], 1, 0, [3, 0]];
        yield 'no codes' => [['success_exit_codes' => []], 1, 0, [0]];
        yield 'codes not a list' => [['success_exit_codes' => 3], 1, 0, [0]];
    }

    /**
     * @param array<mixed> $meta
     * @param list<int> $okCodes
     */
    #[DataProvider('provideMetas')]
    public function testFromMeta(array $meta, int $completions, int $timeLimit, array $okCodes): void
    {
        $settings = JobRunSettings::fromMeta($meta);

        self::assertSame($completions, $settings->completions);
        self::assertSame($timeLimit, $settings->timeLimit);
        self::assertSame($okCodes, $settings->okCodes);
    }
}
