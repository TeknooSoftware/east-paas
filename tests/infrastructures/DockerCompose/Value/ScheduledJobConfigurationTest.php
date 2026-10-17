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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\ConcurrencyPolicy;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\ScheduleOptions;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\JobRunSettings;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\ScheduledJobConfiguration;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ScheduledJobConfiguration::class)]
class ScheduledJobConfigurationTest extends TestCase
{
    public function testDefaultsOfKubernetes(): void
    {
        $configuration = new ScheduledJobConfiguration(
            project: 'prj',
            services: ['job-backup'],
            run: JobRunSettings::fromMeta([]),
            pattern: '^(17) (03) [0-9]{2} [0-9]{2} [0-6]$',
        );

        self::assertSame(
            "project='prj'\n"
            . "services='job-backup'\n"
            . "completions='1'\n"
            . "time_limit='0'\n"
            . "ok_codes='0'\n"
            . "concurrency='allow'\n"
            . "time_zone=''\n"
            . "pattern='^(17) (03) [0-9]{2} [0-9]{2} [0-6]\$'\n"
            . "deadline=''\n"
            . "successful_history='3'\n"
            . "failed_history='1'\n",
            $configuration->toShell(),
        );
    }

    public function testDefinedOptions(): void
    {
        $configuration = new ScheduledJobConfiguration(
            project: 'prj',
            services: ['job-backup', 'job-backup-proxy'],
            run: JobRunSettings::fromMeta(['completions' => 2, 'time_limit' => 600, 'success_exit_codes' => [0, 3]]),
            pattern: '^(00) (00) [0-9]{2} [0-9]{2} [0-6]$',
            options: new ScheduleOptions(
                timeZone: 'Europe/Paris',
                concurrency: ConcurrencyPolicy::Forbid,
                startingDeadline: 0,
                successfulHistory: 0,
                failedHistory: 5,
                suspend: true,
            ),
        );

        self::assertSame(
            "project='prj'\n"
            . "services='job-backup job-backup-proxy'\n"
            . "completions='2'\n"
            . "time_limit='600'\n"
            . "ok_codes='0 3'\n"
            . "concurrency='forbid'\n"
            . "time_zone='Europe/Paris'\n"
            . "pattern='^(00) (00) [0-9]{2} [0-9]{2} [0-6]\$'\n"
            . "deadline='0'\n"
            . "successful_history='0'\n"
            . "failed_history='5'\n",
            $configuration->toShell(),
        );
    }

    public function testEachValueIsQuotedForTheShellWhichSourcesIt(): void
    {
        $configuration = new ScheduledJobConfiguration(
            project: "it's \$(id)",
            services: ['job'],
            run: JobRunSettings::fromMeta([]),
            pattern: "^\\$`x` 'y'",
            options: new ScheduleOptions(timeZone: "Europe/Paris\n"),
        );

        $shell = new Process(
            [
                '/bin/sh',
                '-c',
                'eval "$0"; printf "%s|%s|%s" "$project" "$pattern" "$time_zone"',
                $configuration->toShell(),
            ],
        );
        $shell->mustRun();

        self::assertSame("it's \$(id)|^\\$`x` 'y'|Europe/Paris\n", $shell->getOutput());
    }
}
