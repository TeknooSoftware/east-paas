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

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

use function is_array;
use function str_starts_with;
use function unlink;

/**
 * Runs `jobs/east-paas-pod.sh`, extracted from the deploy playbook template, with fake `docker` and `timeout` in a
 * hermetic PATH: the real `timeout` of the host is never used.
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversNothing]
class JobPodRunnerTest extends TestCase
{
    use JobScriptTestTrait;

    private const string WHEN_JOBS = '(paas_jobs | default([])) | length > 0 '
        . 'or (paas_scheduled_jobs | default([])) | length > 0';

    protected function setUp(): void
    {
        $this->createWorkingDirectory([], ['cat'], ['jobs/east-paas-pod.sh']);
    }

    /**
     * @param array<int, string> $arguments
     * @param array<string, string> $env
     */
    private function runPod(string $shell, array $arguments, array $env = []): Process
    {
        $process = new Process(
            [$shell, 'jobs/east-paas-pod.sh', ...$arguments],
            $this->dir . '/project',
            [
                'PATH' => $this->hermeticPath(),
                'FAKE_LOG' => $this->dir . '/calls.log',
                'FAKE_DIR' => $this->dir,
                'FAKE_CANCEL' => $this->dir . '/cancel',
            ] + $env,
        );
        $process->run();

        return $process;
    }

    public function testTheScriptIsPushedBeforeTheDuringDeploymentJobsWhichRunThroughIt(): void
    {
        $directory = null;
        $script = null;
        $jobs = null;
        foreach (self::tasks() as $index => $task) {
            $file = $task['ansible.builtin.file'] ?? null;
            if (is_array($file) && '{{ paas_project_dir }}/jobs' === ($file['path'] ?? null)) {
                $directory = $index;
                self::assertSame('directory', $file['state']);
                self::assertSame('0750', $file['mode']);
                self::assertSame(self::WHEN_JOBS, $task['when']);
            }

            $copy = $task['ansible.builtin.copy'] ?? null;
            if (is_array($copy) && '{{ paas_project_dir }}/jobs/east-paas-pod.sh' === ($copy['dest'] ?? null)) {
                $script = $index;
                self::assertSame('0750', $copy['mode']);
                self::assertSame(self::WHEN_JOBS, $task['when']);
            }

            $command = $task['ansible.builtin.command'] ?? null;
            if (is_array($command) && '{{ paas_jobs | default([]) }}' === ($task['loop'] ?? null)) {
                $jobs = $index;
                self::assertSame(
                    'sh jobs/east-paas-pod.sh {{ paas_project | quote }} jobs {{ item.timeout | quote }} '
                        . "{{ item.services | map('quote') | join(' ') }}",
                    $command['cmd'],
                );
                self::assertSame('{{ paas_project_dir }}', $command['chdir']);
                self::assertSame('paas_jobs_result.rc not in item.ok_codes', $task['failed_when']);
            }
        }

        self::assertIsInt($directory);
        self::assertIsInt($script);
        self::assertIsInt($jobs);
        self::assertLessThan($script, $directory);
        self::assertLessThan($jobs, $script);
    }

    public function testTheScriptHasNoTemplateDelimiter(): void
    {
        $script = self::extractScript('jobs/east-paas-pod.sh');
        self::assertTrue(str_starts_with($script, "#!/bin/sh\n"));
        self::assertStringNotContainsString('{{', $script);
        self::assertStringNotContainsString('{%', $script);
        self::assertStringNotContainsString('{#', $script);
    }

    #[DataProvider('provideShells')]
    public function testWrongNumberOfArguments(string $shell): void
    {
        $process = $this->runPod($shell, ['prj', 'jobs', '0']);
        self::assertSame(2, $process->getExitCode());
        self::assertStringContainsString('usage: ', $process->getErrorOutput());
        self::assertSame(2, $this->runPod($shell, [])->getExitCode());
        self::assertSame([], $this->calls());
    }

    #[DataProvider('provideShells')]
    public function testACompletionRunsDetachedWithoutItsDependenciesAndIsRemoved(string $shell): void
    {
        $process = $this->runPod($shell, ['prj', 'jobs', '0', 'job-init']);

        self::assertSame(0, $process->getExitCode());
        self::assertSame(
            [
                'docker compose -p prj --profile jobs run -d --no-deps job-init',
                'docker wait cid1',
                'docker logs cid1',
                'docker rm -f cid1',
            ],
            $this->calls(),
        );
        self::assertSame("output of cid1\n", $process->getOutput());
        self::assertSame('', $process->getErrorOutput());
    }

    #[DataProvider('provideShells')]
    public function testExitsWithTheCodeOfTheContainer(string $shell): void
    {
        $process = $this->runPod($shell, ['prj', 'scheduled', '0', 'job-backup'], ['FAKE_EXIT_CODE' => '5']);

        self::assertSame(5, $process->getExitCode());
        self::assertSame(
            [
                'docker compose -p prj --profile scheduled run -d --no-deps job-backup',
                'docker wait cid1',
                'docker logs cid1',
                'docker rm -f cid1',
            ],
            $this->calls(),
        );
        self::assertSame("output of cid1\n", $process->getOutput());
    }

    #[DataProvider('provideShells')]
    public function testTimeLimitNotReached(string $shell): void
    {
        $process = $this->runPod($shell, ['prj', 'jobs', '30', 'job-init']);

        self::assertSame(0, $process->getExitCode());
        self::assertSame(
            [
                'docker compose -p prj --profile jobs run -d --no-deps job-init',
                'timeout 30 docker wait cid1',
                'docker wait cid1',
                'docker logs cid1',
                'docker rm -f cid1',
            ],
            $this->calls(),
        );
        self::assertSame("output of cid1\n", $process->getOutput());
    }

    #[DataProvider('provideShells')]
    public function testTimeLimitReachedStopsTheContainer(string $shell): void
    {
        $process = $this->runPod($shell, ['prj', 'jobs', '30', 'job-init'], ['FAKE_TIMEOUT' => '1']);

        self::assertSame(124, $process->getExitCode());
        self::assertSame(
            [
                'docker compose -p prj --profile jobs run -d --no-deps job-init',
                'timeout 30 docker wait cid1',
                'docker stop cid1',
                'docker logs cid1',
                'docker rm -f cid1',
            ],
            $this->calls(),
        );
        self::assertSame(
            "output of cid1\neast-paas: the container is stopped, the time limit of 30 seconds is reached\n",
            $process->getOutput(),
        );
    }

    #[DataProvider('provideShells')]
    public function testAFailureOfTheWaitWithoutTimeLimitStopsTheContainer(string $shell): void
    {
        $process = $this->runPod($shell, ['prj', 'jobs', '0', 'job-init'], ['FAKE_WAIT_FAIL' => '1']);

        self::assertSame(124, $process->getExitCode());
        self::assertSame(
            [
                'docker compose -p prj --profile jobs run -d --no-deps job-init',
                'docker wait cid1',
                'docker stop cid1',
                'docker logs cid1',
                'docker rm -f cid1',
            ],
            $this->calls(),
        );
        self::assertSame(
            "output of cid1\neast-paas: the container is stopped, the wait failed\n",
            $process->getOutput(),
        );
        self::assertSame("no such container\n", $process->getErrorOutput());
    }

    #[DataProvider('provideShells')]
    public function testAFailureToStartTheContainerFails(string $shell): void
    {
        $process = $this->runPod($shell, ['prj', 'jobs', '0', 'job-init'], ['FAKE_RUN_FAIL' => '1']);

        self::assertSame(1, $process->getExitCode());
        self::assertSame(['docker compose -p prj --profile jobs run -d --no-deps job-init'], $this->calls());
        self::assertSame('', $process->getOutput());
        self::assertSame("no such service\n", $process->getErrorOutput());
    }

    #[DataProvider('provideShells')]
    public function testAnEmptyContainerIdFails(string $shell): void
    {
        $process = $this->runPod($shell, ['prj', 'jobs', '0', 'job-init'], ['FAKE_RUN_EMPTY' => '1']);

        self::assertSame(1, $process->getExitCode());
        self::assertSame(['docker compose -p prj --profile jobs run -d --no-deps job-init'], $this->calls());
        self::assertSame('', $process->getOutput());
    }

    #[DataProvider('provideShells')]
    public function testANonNumericExitCodeIsAFailure(string $shell): void
    {
        $process = $this->runPod($shell, ['prj', 'jobs', '0', 'job-init'], ['FAKE_EXIT_CODE' => 'abc']);

        self::assertSame(1, $process->getExitCode());
        self::assertSame(
            [
                'docker compose -p prj --profile jobs run -d --no-deps job-init',
                'docker wait cid1',
                'docker logs cid1',
                'docker rm -f cid1',
            ],
            $this->calls(),
        );
    }

    #[DataProvider('provideShells')]
    public function testAContainerWithoutExitCodeIsAFailure(string $shell): void
    {
        $env = ['FAKE_WAIT_SILENT' => '1'];
        self::assertSame(1, $this->runPod($shell, ['prj', 'jobs', '0', 'job-init'], $env)->getExitCode());
        self::assertSame(
            1,
            $this->runPod($shell, ['prj', 'jobs', '0', 'job-pod', 'job-pod-side'], $env)->getExitCode(),
        );
    }

    #[DataProvider('provideShells')]
    public function testAMissingTimeoutFailsOnlyWithATimeLimit(string $shell): void
    {
        unlink($this->dir . '/bin/timeout');

        $process = $this->runPod($shell, ['prj', 'jobs', '30', 'job-init']);
        self::assertSame(2, $process->getExitCode());
        self::assertStringContainsString('timeout (coreutils) is missing', $process->getErrorOutput());
        self::assertSame([], $this->calls());

        self::assertSame(0, $this->runPod($shell, ['prj', 'jobs', '0', 'job-init'])->getExitCode());
        self::assertSame(
            [
                'docker compose -p prj --profile jobs run -d --no-deps job-init',
                'docker wait cid1',
                'docker logs cid1',
                'docker rm -f cid1',
            ],
            $this->calls(),
        );
    }

    #[DataProvider('provideShells')]
    public function testAPodOfSeveralServicesRunsAsRegularContainersWaitedForTogether(string $shell): void
    {
        $process = $this->runPod($shell, ['prj', 'jobs', '0', 'job-pod', 'job-pod-side', 'job-pod-other']);

        self::assertSame(0, $process->getExitCode());
        self::assertSame(
            [
                'docker compose -p prj --profile jobs up -d --force-recreate --no-deps job-pod job-pod-side '
                    . 'job-pod-other',
                self::regularContainerLookup('job-pod'),
                self::regularContainerLookup('job-pod-side'),
                self::regularContainerLookup('job-pod-other'),
                'docker wait id-job-pod id-job-pod-side id-job-pod-other',
                'docker logs id-job-pod',
                'docker logs id-job-pod-side',
                'docker logs id-job-pod-other',
                'docker rm -f id-job-pod id-job-pod-side id-job-pod-other',
            ],
            $this->calls(),
        );
        self::assertSame(
            "east-paas: job-pod\noutput of id-job-pod\neast-paas: job-pod-side\noutput of id-job-pod-side\n"
            . "east-paas: job-pod-other\noutput of id-job-pod-other\n",
            $process->getOutput(),
        );
        //The progress of `up` is not mixed with the output of the containers
        self::assertSame("progress of up\n", $process->getErrorOutput());
    }

    /**
     * @return iterable<string, array{string, string, int}>
     */
    public static function provideExitCodesOfAPod(): iterable
    {
        foreach (self::provideShells() as $name => [$shell]) {
            yield $name . ': a sidecar fails' => [$shell, 'id-job-pod-side=3 id-job-pod-other=5', 3];
            yield $name . ': the anchor fails first' => [$shell, 'id-job-pod=2 id-job-pod-side=3', 2];
            yield $name . ': a non numeric code' => [$shell, 'id-job-pod-other=abc', 1];
            yield $name . ': all succeed' => [$shell, '', 0];
        }
    }

    #[DataProvider('provideExitCodesOfAPod')]
    public function testAPodExitsWithTheFirstNonZeroCodeInTheOrderOfTheServices(
        string $shell,
        string $codes,
        int $expected,
    ): void {
        $process = $this->runPod(
            $shell,
            ['prj', 'scheduled', '0', 'job-pod', 'job-pod-side', 'job-pod-other'],
            ['FAKE_EXIT_CODES' => $codes],
        );

        self::assertSame($expected, $process->getExitCode());
        self::assertContains('docker wait id-job-pod id-job-pod-side id-job-pod-other', $this->calls());
        self::assertContains('docker rm -f id-job-pod id-job-pod-side id-job-pod-other', $this->calls());
    }

    #[DataProvider('provideShells')]
    public function testTimeLimitReachedStopsAllTheContainersOfAPod(string $shell): void
    {
        $process = $this->runPod($shell, ['prj', 'jobs', '30', 'job-pod', 'job-pod-side'], ['FAKE_TIMEOUT' => '1']);

        self::assertSame(124, $process->getExitCode());
        self::assertSame(
            [
                'docker compose -p prj --profile jobs up -d --force-recreate --no-deps job-pod job-pod-side',
                self::regularContainerLookup('job-pod'),
                self::regularContainerLookup('job-pod-side'),
                'timeout 30 docker wait id-job-pod id-job-pod-side',
                'docker stop id-job-pod id-job-pod-side',
                'docker logs id-job-pod',
                'docker logs id-job-pod-side',
                'docker rm -f id-job-pod id-job-pod-side',
            ],
            $this->calls(),
        );
        self::assertSame(
            "east-paas: job-pod\noutput of id-job-pod\neast-paas: job-pod-side\noutput of id-job-pod-side\n"
            . "east-paas: the container is stopped, the time limit of 30 seconds is reached\n",
            $process->getOutput(),
        );
    }

    #[DataProvider('provideShells')]
    public function testAFailureOfTheWaitOfAPodStopsAllItsContainers(string $shell): void
    {
        $process = $this->runPod($shell, ['prj', 'jobs', '0', 'job-pod', 'job-pod-side'], ['FAKE_WAIT_FAIL' => '1']);

        self::assertSame(124, $process->getExitCode());
        self::assertContains('docker stop id-job-pod id-job-pod-side', $this->calls());
        self::assertContains('docker rm -f id-job-pod id-job-pod-side', $this->calls());
        self::assertStringEndsWith("east-paas: the container is stopped, the wait failed\n", $process->getOutput());
    }

    #[DataProvider('provideShells')]
    public function testTheOneOffContainersOfTheServicesOfAPodAreIgnored(string $shell): void
    {
        $process = $this->runPod(
            $shell,
            ['prj', 'scheduled', '0', 'job-pod', 'job-pod-side'],
            //Left by a `docker compose run` of an operator
            ['FAKE_PS_IDS' => 'job-pod=run-1 job-pod-side=run-2', 'FAKE_EXIT_CODES' => 'run-1=5 run-2=6'],
        );

        self::assertSame(0, $process->getExitCode());
        self::assertSame(
            [
                'docker compose -p prj --profile scheduled up -d --force-recreate --no-deps job-pod job-pod-side',
                self::regularContainerLookup('job-pod'),
                self::regularContainerLookup('job-pod-side'),
                'docker wait id-job-pod id-job-pod-side',
                'docker logs id-job-pod',
                'docker logs id-job-pod-side',
                'docker rm -f id-job-pod id-job-pod-side',
            ],
            $this->calls(),
        );
    }

    #[DataProvider('provideShells')]
    public function testAFailureToStartAPodWithoutContainerFails(string $shell): void
    {
        $process = $this->runPod(
            $shell,
            ['prj', 'jobs', '0', 'job-pod', 'job-pod-side'],
            ['FAKE_UP_FAIL' => '1', 'FAKE_PS_EMPTY' => 'job-pod job-pod-side'],
        );

        self::assertSame(1, $process->getExitCode());
        self::assertSame(
            [
                'docker compose -p prj --profile jobs up -d --force-recreate --no-deps job-pod job-pod-side',
                self::regularContainerLookup('job-pod'),
                self::regularContainerLookup('job-pod-side'),
            ],
            $this->calls(),
        );
        self::assertSame('', $process->getOutput());
        self::assertSame("progress of up\nup failed\n", $process->getErrorOutput());
    }

    private static function statusLookup(string $id, string $status): string
    {
        return 'docker ps -aq --filter id=' . $id . ' --filter status=' . $status;
    }

    /**
     * @return iterable<string, array{string, string, array<int, string>}>
     */
    public static function provideFailuresToStartAPod(): iterable
    {
        foreach (self::provideShells() as $name => [$shell]) {
            yield $name . ': a sidecar not started while the anchor runs' => [
                $shell,
                'id-job-pod-side=created',
                [
                    self::statusLookup('id-job-pod', 'created'),
                    self::statusLookup('id-job-pod-side', 'created'),
                    self::statusLookup('id-job-pod', 'exited'),
                ],
            ];
            yield $name . ': the anchor not started' => [
                $shell,
                'id-job-pod=created id-job-pod-side=created',
                [
                    self::statusLookup('id-job-pod', 'created'),
                    self::statusLookup('id-job-pod-side', 'created'),
                    self::statusLookup('id-job-pod', 'exited'),
                ],
            ];
            yield $name . ': all started' => [
                $shell,
                'id-job-pod=exited',
                [
                    self::statusLookup('id-job-pod', 'created'),
                    self::statusLookup('id-job-pod-side', 'created'),
                ],
            ];
        }
    }

    /**
     * @param array<int, string> $lookups
     */
    #[DataProvider('provideFailuresToStartAPod')]
    public function testAFailureToStartAPodRemovesItsContainersAndFails(
        string $shell,
        string $states,
        array $lookups,
    ): void {
        $process = $this->runPod(
            $shell,
            ['prj', 'jobs', '0', 'job-pod', 'job-pod-side'],
            ['FAKE_UP_FAIL' => '1', 'FAKE_STATES' => $states],
        );

        self::assertSame(1, $process->getExitCode());
        self::assertSame(
            [
                'docker compose -p prj --profile jobs up -d --force-recreate --no-deps job-pod job-pod-side',
                self::regularContainerLookup('job-pod'),
                self::regularContainerLookup('job-pod-side'),
                ...$lookups,
                'docker logs id-job-pod',
                'docker logs id-job-pod-side',
                'docker rm -f id-job-pod id-job-pod-side',
            ],
            $this->calls(),
        );
        self::assertSame(
            "east-paas: job-pod\noutput of id-job-pod\neast-paas: job-pod-side\noutput of id-job-pod-side\n",
            $process->getOutput(),
        );
        self::assertSame("progress of up\nup failed\n", $process->getErrorOutput());
    }

    /**
     * @return iterable<string, array{string, string, int}>
     */
    public static function provideCodesOfAPodWhoseAnchorEndedFirst(): iterable
    {
        foreach (self::provideShells() as $name => [$shell]) {
            yield $name . ': all succeed' => [$shell, '', 0];
            yield $name . ': the anchor fails' => [$shell, 'id-job-pod=4 id-job-pod-side=3', 4];
            yield $name . ': the started sidecar fails' => [$shell, 'id-job-pod-side=3', 3];
        }
    }

    #[DataProvider('provideCodesOfAPodWhoseAnchorEndedFirst')]
    public function testAPodWhoseAnchorEndedBeforeASidecarStartedEndsWithItsAnchor(
        string $shell,
        string $codes,
        int $expected,
    ): void {
        $process = $this->runPod(
            $shell,
            ['prj', 'jobs', '0', 'job-pod', 'job-pod-side', 'job-pod-other'],
            [
                'FAKE_UP_FAIL' => '1',
                //job-pod-other could not join the network namespace of job-pod, already ended
                'FAKE_STATES' => 'id-job-pod=exited id-job-pod-other=created',
                'FAKE_EXIT_CODES' => $codes,
            ],
        );

        self::assertSame($expected, $process->getExitCode());
        self::assertSame(
            [
                'docker compose -p prj --profile jobs up -d --force-recreate --no-deps job-pod job-pod-side '
                    . 'job-pod-other',
                self::regularContainerLookup('job-pod'),
                self::regularContainerLookup('job-pod-side'),
                self::regularContainerLookup('job-pod-other'),
                self::statusLookup('id-job-pod', 'created'),
                self::statusLookup('id-job-pod-side', 'created'),
                self::statusLookup('id-job-pod-other', 'created'),
                self::statusLookup('id-job-pod', 'exited'),
                'docker wait id-job-pod id-job-pod-side',
                'docker logs id-job-pod',
                'docker logs id-job-pod-side',
                'docker logs id-job-pod-other',
                'docker rm -f id-job-pod id-job-pod-side id-job-pod-other',
            ],
            $this->calls(),
        );
        self::assertSame(
            "east-paas: job-pod\noutput of id-job-pod\neast-paas: job-pod-side\noutput of id-job-pod-side\n"
            . "east-paas: job-pod-other\noutput of id-job-pod-other\n",
            $process->getOutput(),
        );
    }

    #[DataProvider('provideShells')]
    public function testTheTimeLimitOfAPodWhoseAnchorEndedFirstStopsItsStartedContainers(string $shell): void
    {
        $process = $this->runPod(
            $shell,
            ['prj', 'jobs', '30', 'job-pod', 'job-pod-side', 'job-pod-other'],
            [
                'FAKE_UP_FAIL' => '1',
                'FAKE_TIMEOUT' => '1',
                'FAKE_STATES' => 'id-job-pod=exited id-job-pod-other=created',
            ],
        );

        self::assertSame(124, $process->getExitCode());
        self::assertContains('timeout 30 docker wait id-job-pod id-job-pod-side', $this->calls());
        self::assertContains('docker stop id-job-pod id-job-pod-side', $this->calls());
        self::assertContains('docker rm -f id-job-pod id-job-pod-side id-job-pod-other', $this->calls());
    }

    /**
     * @return iterable<string, array{string, string, array<int, string>}>
     */
    public static function provideMissingContainersOfAPod(): iterable
    {
        foreach (self::provideShells() as $name => [$shell]) {
            yield $name . ': a sidecar' => [$shell, 'job-pod-side', ['job-pod', 'job-pod-other']];
            yield $name . ': the anchor' => [$shell, 'job-pod', ['job-pod-side', 'job-pod-other']];
        }
    }

    /**
     * @param array<int, string> $found
     */
    #[DataProvider('provideMissingContainersOfAPod')]
    public function testAMissingContainerOfAPodRemovesTheOthersAndFails(
        string $shell,
        string $missing,
        array $found,
    ): void {
        $process = $this->runPod(
            $shell,
            ['prj', 'jobs', '0', 'job-pod', 'job-pod-side', 'job-pod-other'],
            ['FAKE_PS_EMPTY' => $missing],
        );

        self::assertSame(1, $process->getExitCode());
        self::assertSame(
            [
                'docker compose -p prj --profile jobs up -d --force-recreate --no-deps job-pod job-pod-side '
                    . 'job-pod-other',
                self::regularContainerLookup('job-pod'),
                self::regularContainerLookup('job-pod-side'),
                self::regularContainerLookup('job-pod-other'),
                'docker logs id-' . $found[0],
                'docker logs id-' . $found[1],
                'docker rm -f id-' . $found[0] . ' id-' . $found[1],
            ],
            $this->calls(),
        );
        self::assertSame(
            "east-paas: $found[0]\noutput of id-$found[0]\neast-paas: $found[1]\noutput of id-$found[1]\n",
            $process->getOutput(),
        );
    }

    #[DataProvider('provideShells')]
    public function testNoContainerOfAPodFails(string $shell): void
    {
        $process = $this->runPod(
            $shell,
            ['prj', 'jobs', '0', 'job-pod', 'job-pod-side'],
            ['FAKE_PS_EMPTY' => 'job-pod job-pod-side'],
        );

        self::assertSame(1, $process->getExitCode());
        self::assertSame(
            [
                'docker compose -p prj --profile jobs up -d --force-recreate --no-deps job-pod job-pod-side',
                self::regularContainerLookup('job-pod'),
                self::regularContainerLookup('job-pod-side'),
            ],
            $this->calls(),
        );
    }
}
