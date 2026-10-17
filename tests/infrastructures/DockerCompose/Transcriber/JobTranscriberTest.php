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

namespace Teknoo\Tests\East\Paas\Infrastructures\DockerCompose\Transcriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Container;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\ConcurrencyPolicy;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\Planning;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\ScheduleOptions;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\SuccessCondition;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod\RestartPolicy;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Resource;
use Teknoo\East\Paas\Compilation\CompiledDeployment\ResourceSet;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\AccumulatorInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Accumulator;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Exception\InvalidConfigurationException;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\JobTranscriber;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(JobTranscriber::class)]
class JobTranscriberTest extends TestCase
{
    private function buildTranscriber(): JobTranscriber
    {
        return new JobTranscriber();
    }

    private function buildJob(
        Planning $planning,
        ?string $schedule = null,
        ?ScheduleOptions $options = null,
    ): Job {
        $pod = new Pod(
            name: 'migrate',
            replicas: 1,
            containers: [
                new Container('migrate', 'registry/php', '8.3', [], [], ['STEP' => 'db']),
            ],
            restartPolicy: RestartPolicy::OnFailure,
        );

        return new Job(
            name: 'init',
            pods: ['migrate' => $pod],
            completionsCount: 2,
            isParallel: true,
            successCondition: new SuccessCondition([0], [1, 2]),
            timeLimit: 120,
            planning: $planning,
            planningSchedule: $schedule,
            scheduleOptions: $options,
        );
    }

    /**
     * @param int $calls calls of the callback of foreachJob() (one per pod of the job)
     */
    private function transcribeJob(Job $job, Accumulator $accumulator, PromiseInterface $promise, int $calls = 1): void
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachJob')
            ->willReturnCallback(function (callable $callback) use ($cd, $job, $calls): CompiledDeploymentInterface {
                for ($call = 0; $call < $calls; $call++) {
                    $callback($job, [], [], 'prj');
                }

                return $cd;
            });

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            accumulator: $accumulator,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );
    }

    public function testTranscribeDuringDeployment(): void
    {
        $job = $this->buildJob(Planning::DuringDeployment);

        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachJob')
            ->willReturnCallback(function (callable $callback) use ($cd, $job): CompiledDeploymentInterface {
                $callback($job, [], [], 'prj');

                return $cd;
            });

        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            accumulator: $generation,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );

        $services = $generation->getComposeFile()['services'];

        self::assertArrayHasKey('prj-init-migrate', $services);
        $service = $services['prj-init-migrate'];
        self::assertSame('registry/php:8.3', $service['image']);
        self::assertSame(['jobs'], $service['profiles']);
        self::assertSame('no', $service['restart']);
        self::assertSame(
            [
                'parallel' => true,
                'completions' => 2,
                'time_limit' => 120,
                'success_exit_codes' => [0],
                'failure_exit_codes' => [1, 2],
            ],
            $service['x-paas-job'],
        );
    }

    public function testTranscribeScheduled(): void
    {
        $job = $this->buildJob(Planning::Scheduled, '0 */3 * * *');
        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->with($this->callback(fn (array $result): bool => isset($result['services']['prj-init-migrate'])));
        $promise->expects($this->never())->method('fail');

        $this->transcribeJob($job, $generation, $promise);

        $services = $generation->getComposeFile()['services'];
        self::assertSame(['prj-init-migrate'], array_keys($services));

        $service = $services['prj-init-migrate'];
        self::assertSame('registry/php:8.3', $service['image']);
        self::assertSame(['scheduled'], $service['profiles']);
        //The restart policy of the pod is overridden: a run is not restarted by Docker
        self::assertSame('no', $service['restart']);
        self::assertSame(
            [
                'parallel' => true,
                'completions' => 2,
                'time_limit' => 120,
                'success_exit_codes' => [0],
                'failure_exit_codes' => [1, 2],
            ],
            $service['x-paas-job'],
        );
        self::assertSame(
            [
                'schedule' => '0 */3 * * *',
                'concurrency' => 'allow',
                'suspend' => false,
            ],
            $service['x-paas-schedule'],
        );

        //The options not defined keep the defaults of Kubernetes
        self::assertSame(
            [
                'jobs/prj-init-migrate.conf' => "project='default-prj'\n"
                    . "services='prj-init-migrate'\n"
                    . "completions='2'\n"
                    . "time_limit='120'\n"
                    . "ok_codes='0'\n"
                    . "concurrency='allow'\n"
                    . "time_zone=''\n"
                    . "pattern='^(00) (00|03|06|09|12|15|18|21) [0-9]{2} [0-9]{2} [0-6]\$'\n"
                    . "deadline=''\n"
                    . "successful_history='3'\n"
                    . "failed_history='1'\n",
            ],
            $generation->getFiles(),
        );
        self::assertSame([], $generation->getWarnings());
        self::assertSame([], $generation->getJobsToRun());
        self::assertSame(
            [
                [
                    'name' => 'east-paas:default-prj:prj-init-migrate',
                    'service' => 'prj-init-migrate',
                    'services' => ['prj-init-migrate'],
                    'disabled' => false,
                    'job' => "PATH=/usr/local/bin:/usr/bin:/bin sh jobs/east-paas-cron.sh 'prj-init-migrate' "
                        . "'0 */3 * * *' >>'jobs/prj-init-migrate/events.log' 2>&1",
                ],
            ],
            $generation->getScheduledJobs(),
        );
    }

    public function testTranscribeScheduledWithATimeZoneAndOptions(): void
    {
        $job = $this->buildJob(
            Planning::Scheduled,
            '17 3 * * *',
            new ScheduleOptions(
                timeZone: 'Europe/Paris',
                concurrency: ConcurrencyPolicy::Forbid,
                startingDeadline: 300,
                successfulHistory: 5,
                failedHistory: 0,
                suspend: true,
            ),
        );
        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $this->transcribeJob($job, $generation, $promise);

        $service = $generation->getComposeFile()['services']['prj-init-migrate'];
        self::assertSame(['scheduled'], $service['profiles']);
        self::assertSame(
            [
                'schedule' => '17 3 * * *',
                'time_zone' => 'Europe/Paris',
                'concurrency' => 'forbid',
                'suspend' => true,
                'starting_deadline' => 300,
                'successful_history' => 5,
                'failed_history' => 0,
            ],
            $service['x-paas-schedule'],
        );

        self::assertSame(
            [
                'jobs/prj-init-migrate.conf' => "project='default-prj'\n"
                    . "services='prj-init-migrate'\n"
                    . "completions='2'\n"
                    . "time_limit='120'\n"
                    . "ok_codes='0'\n"
                    . "concurrency='forbid'\n"
                    . "time_zone='Europe/Paris'\n"
                    . "pattern='^(17) (03) [0-9]{2} [0-9]{2} [0-6]\$'\n"
                    . "deadline='300'\n"
                    . "successful_history='5'\n"
                    . "failed_history='0'\n",
            ],
            $generation->getFiles(),
        );
        self::assertSame([], $generation->getWarnings());

        //Suspended: the entry is disabled, the configuration is still pushed for the resume
        $entries = $generation->getScheduledJobs();
        self::assertCount(1, $entries);
        self::assertTrue($entries[0]['disabled']);
        self::assertSame(
            "PATH=/usr/local/bin:/usr/bin:/bin sh jobs/east-paas-cron.sh 'prj-init-migrate' '17 3 * * *' "
            . ">>'jobs/prj-init-migrate/events.log' 2>&1",
            $entries[0]['job'],
        );
    }

    public function testTranscribeScheduledWithSuccessExitCodes(): void
    {
        $job = new Job(
            name: 'init',
            pods: [
                'migrate' => new Pod(
                    name: 'migrate',
                    replicas: 1,
                    containers: [new Container('migrate', 'registry/php', '8.3', [], [], [])],
                ),
            ],
            successCondition: new SuccessCondition([0, 3, 3], [1]),
            planning: Planning::Scheduled,
            planningSchedule: '@hourly',
        );
        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $this->transcribeJob($job, $generation, $promise);

        self::assertStringContainsString(
            "\nok_codes='0 3'\n",
            $generation->getFiles()['jobs/prj-init-migrate.conf'],
        );
    }

    public function testTranscribeScheduledWithSeveralPods(): void
    {
        $pods = [];
        foreach (['dump', 'upload'] as $name) {
            $pods[$name] = new Pod(
                name: $name,
                replicas: 1,
                containers: [new Container($name, 'registry/alpine', '3.20', [], [], [])],
            );
        }

        $job = new Job(
            name: 'backup',
            pods: $pods,
            planning: Planning::Scheduled,
            planningSchedule: '@daily',
            scheduleOptions: new ScheduleOptions(timeZone: 'Asia/Kolkata', concurrency: ConcurrencyPolicy::Replace),
        );
        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(2))->method('success');
        $promise->expects($this->never())->method('fail');

        $this->transcribeJob($job, $generation, $promise, 2);

        $services = $generation->getComposeFile()['services'];
        self::assertSame(['prj-backup-dump', 'prj-backup-upload'], array_keys($services));
        self::assertSame('replace', $services['prj-backup-upload']['x-paas-schedule']['concurrency']);

        $files = $generation->getFiles();
        self::assertSame(['jobs/prj-backup-dump.conf', 'jobs/prj-backup-upload.conf'], array_keys($files));
        foreach (['dump', 'upload'] as $name) {
            self::assertSame(
                "project='default-prj'\n"
                . "services='prj-backup-$name'\n"
                . "completions='1'\n"
                . "time_limit='0'\n"
                . "ok_codes='0'\n"
                . "concurrency='replace'\n"
                . "time_zone='Asia/Kolkata'\n"
                . "pattern='^(00) (00) [0-9]{2} [0-9]{2} [0-6]\$'\n"
                . "deadline=''\n"
                . "successful_history='3'\n"
                . "failed_history='1'\n",
                $files['jobs/prj-backup-' . $name . '.conf'],
            );
        }

        self::assertCount(2, $generation->getScheduledJobs());
    }

    public function testTranscribeScheduledEveryAddsWarning(): void
    {
        $job = $this->buildJob(Planning::Scheduled, '@every 1h');
        $generation = new Accumulator('default-prj', 'private');

        $warning = 'The scheduled job `init` uses the schedule `@every 1h`, not supported on a Docker Compose host, '
            . 'it is not deployed';

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success')->with(['warning' => $warning]);
        $promise->expects($this->never())->method('fail');

        $this->transcribeJob($job, $generation, $promise);

        self::assertSame([], $generation->getComposeFile());
        self::assertSame([], $generation->getFiles());
        self::assertSame([$warning], $generation->getWarnings());
    }

    public function testTranscribeScheduledEveryAddsWarningOnceForAJobWithSeveralPods(): void
    {
        $job = $this->buildJob(Planning::Scheduled, '@every 1h30m', new ScheduleOptions(timeZone: 'Europe/Paris'));
        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $this->transcribeJob($job, $generation, $promise, 3);

        self::assertSame([], $generation->getComposeFile());
        self::assertSame([], $generation->getFiles());
        self::assertCount(1, $generation->getWarnings());
    }

    /**
     * @return array<string, Pod>
     */
    private static function podsWithASidecar(): array
    {
        return [
            'files' => new Pod(
                name: 'files',
                replicas: 1,
                containers: [new Container('files', 'registry/alpine', '3.20', [], [], [])],
            ),
            'dump' => new Pod(
                name: 'dump',
                replicas: 1,
                containers: [
                    new Container('dump', 'registry/alpine', '3.20', [], [], []),
                    new Container('proxy', 'registry/proxy', '1.0', [], [], []),
                ],
            ),
        ];
    }

    public function testTranscribeScheduledWithAPodWithSeveralContainers(): void
    {
        $job = new Job(
            name: 'backup',
            pods: self::podsWithASidecar(),
            planning: Planning::Scheduled,
            planningSchedule: '0 3 * * *',
            scheduleOptions: new ScheduleOptions(timeZone: 'Europe/Paris'),
        );
        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(2))->method('success');
        $promise->expects($this->never())->method('fail');

        $this->transcribeJob($job, $generation, $promise, 2);

        $services = $generation->getComposeFile()['services'];
        self::assertSame(['prj-backup-files', 'prj-backup-dump', 'prj-backup-dump-proxy'], array_keys($services));
        self::assertArrayNotHasKey('network_mode', $services['prj-backup-dump']);
        self::assertSame('service:prj-backup-dump', $services['prj-backup-dump-proxy']['network_mode']);
        self::assertSame(['scheduled'], $services['prj-backup-dump-proxy']['profiles']);
        self::assertSame('no', $services['prj-backup-dump-proxy']['restart']);
        self::assertSame(
            $services['prj-backup-dump']['x-paas-schedule'],
            $services['prj-backup-dump-proxy']['x-paas-schedule'],
        );

        $files = $generation->getFiles();
        self::assertSame(['jobs/prj-backup-files.conf', 'jobs/prj-backup-dump.conf'], array_keys($files));
        self::assertStringContainsString("\nservices='prj-backup-files'\n", $files['jobs/prj-backup-files.conf']);
        self::assertStringContainsString(
            "\nservices='prj-backup-dump prj-backup-dump-proxy'\n",
            $files['jobs/prj-backup-dump.conf'],
        );
        self::assertSame([], $generation->getWarnings());
        self::assertSame([], $generation->getJobsToRun());

        $entries = $generation->getScheduledJobs();
        self::assertSame(
            ['east-paas:default-prj:prj-backup-files', 'east-paas:default-prj:prj-backup-dump'],
            array_column($entries, 'name'),
        );
        self::assertSame(['prj-backup-dump', 'prj-backup-dump-proxy'], $entries[1]['services']);
        self::assertSame(
            "PATH=/usr/local/bin:/usr/bin:/bin sh jobs/east-paas-cron.sh 'prj-backup-dump' '0 3 * * *' "
            . ">>'jobs/prj-backup-dump/events.log' 2>&1",
            $entries[1]['job'],
        );
    }

    public function testTranscribeDuringDeploymentWithAPodWithSeveralContainers(): void
    {
        $job = new Job(
            name: 'backup',
            pods: self::podsWithASidecar(),
            planning: Planning::DuringDeployment,
        );
        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $this->transcribeJob($job, $generation, $promise);

        $services = $generation->getComposeFile()['services'];
        self::assertSame(['prj-backup-files', 'prj-backup-dump', 'prj-backup-dump-proxy'], array_keys($services));
        //The sidecar joins the network namespace of the anchor of the job, not a service named after the pod
        self::assertSame('service:prj-backup-dump', $services['prj-backup-dump-proxy']['network_mode']);
        self::assertSame(['jobs'], $services['prj-backup-dump-proxy']['profiles']);
        self::assertSame([], $generation->getWarnings());
        self::assertSame(
            [
                [
                    'service' => 'prj-backup-files',
                    'services' => ['prj-backup-files'],
                    'run' => 1,
                    'completions' => 1,
                    'timeout' => 0,
                    'ok_codes' => [0],
                ],
                [
                    'service' => 'prj-backup-dump',
                    'services' => ['prj-backup-dump', 'prj-backup-dump-proxy'],
                    'run' => 1,
                    'completions' => 1,
                    'timeout' => 0,
                    'ok_codes' => [0],
                ],
            ],
            $generation->getJobsToRun(),
        );
    }

    /**
     * @return iterable<string, array{Planning, ?string}>
     */
    public static function providePlannings(): iterable
    {
        yield 'during deployment' => [Planning::DuringDeployment, null];
        yield 'scheduled' => [Planning::Scheduled, '0 3 * * *'];
    }

    #[DataProvider('providePlannings')]
    public function testTheReplicasOfAJobPodAreIgnored(Planning $planning, ?string $schedule): void
    {
        $job = new Job(
            name: 'backup',
            pods: [
                'dump' => new Pod(
                    name: 'dump',
                    replicas: 2,
                    containers: [
                        new Container(
                            name: 'dump',
                            image: 'registry/alpine',
                            version: '3.20',
                            resources: new ResourceSet([new Resource('memory', '64Mi', '128Mi')]),
                        ),
                        new Container('proxy', 'registry/proxy', '1.0', [], [], []),
                    ],
                ),
                'files' => new Pod(
                    name: 'files',
                    replicas: 3,
                    containers: [new Container('files', 'registry/alpine', '3.20', [], [], [])],
                ),
            ],
            planning: $planning,
            planningSchedule: $schedule,
        );
        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $this->transcribeJob($job, $generation, $promise);

        $services = $generation->getComposeFile()['services'];
        self::assertSame(
            ['resources' => ['reservations' => ['memory' => '64M'], 'limits' => ['memory' => '128M']]],
            $services['prj-backup-dump']['deploy'],
        );
        self::assertArrayNotHasKey('deploy', $services['prj-backup-dump-proxy']);
        self::assertArrayNotHasKey('deploy', $services['prj-backup-files']);
    }

    public function testTranscribeScheduledWithAnInvalidScheduleFails(): void
    {
        $job = $this->buildJob(Planning::Scheduled, '5-1 * * * *');
        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())
            ->method('fail')
            ->with($this->isInstanceOf(InvalidConfigurationException::class));

        $this->transcribeJob($job, $generation, $promise);

        self::assertSame([], $generation->getComposeFile());
    }

    public function testTranscribeFailure(): void
    {
        $job = $this->buildJob(Planning::DuringDeployment);

        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachJob')
            ->willReturnCallback(function (callable $callback) use ($cd, $job): CompiledDeploymentInterface {
                $callback($job, [], [], 'prj');

                return $cd;
            });

        $accumulator = $this->createMock(AccumulatorInterface::class);
        $accumulator->method('getNetworkName')->willReturn('default-prj-private');
        $accumulator->expects($this->once())
            ->method('addService')
            ->willThrowException(new RuntimeException('boom'));

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail')->with($this->isInstanceOf(RuntimeException::class));

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            accumulator: $accumulator,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );
    }
}
