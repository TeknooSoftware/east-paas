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

namespace Teknoo\Tests\East\Paas\Compilation\Compiler;

use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use stdClass;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\CompletionMode;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\ConcurrencyPolicy;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\Planning;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\ScheduleOptions;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\Compiler\JobCompiler;
use Teknoo\East\Paas\Compilation\Compiler\PodCompiler;
use Teknoo\East\Paas\Compilation\Compiler\ResourceManager;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(JobCompiler::class)]
class JobCompilerTest extends TestCase
{
    private (PodCompiler&MockObject)|(PodCompiler&Stub)|null $podCompiler = null;

    private function getPodCompiler(bool $stub = false): (PodCompiler&Stub)|(PodCompiler&MockObject)
    {
        if (!$this->podCompiler instanceof PodCompiler) {
            if ($stub) {
                $this->podCompiler = $this->createStub(PodCompiler::class);
            } else {
                $this->podCompiler = $this->createMock(PodCompiler::class);
            }
        }

        return $this->podCompiler;
    }

    public function buildCompiler(): JobCompiler
    {
        return new JobCompiler(
            $this->getPodCompiler(true),
            [
                'foo-ext' => [
                    'is-parallel' => true,
                    'completions' => [
                        'count' => 2,
                        'time-limit' => 10,
                        'shelf-life' => 20,
                    ],
                ],
            ],
        );
    }

    private function getDefinitionsArray(): array
    {
        return [
            'job1' => [
                'pods' => [
                    'foo' => []
                ],
            ],
            'job2' => [
                'pods' => [
                    'foo' => []
                ],
                'completions' => [
                    'mode' => CompletionMode::Indexed->value,
                    'count' => 2,
                ],
                'schedule' => '*/5 * * * *',
                'shelf-life' => null,
            ],
            'job3' => [
                'pods' => [
                    'foo' => []
                ],
                'planning' => Planning::Scheduled->value,
                'schedule' => '*/5 * * * *',
                'completions' => [
                    'success-on' => [0],
                    'fail-on' => [1],
                    'time-limit' => 10,
                    'shelf-life' => 20,
                ]
            ],
            'job4' => [
                'pods' => [
                    'foo' => []
                ],
                'planning' => Planning::DuringDeployment->value,
                'completions' => [
                    'shelf-life' => null,
                ]
            ]
        ];
    }

    public function testCompileWithoutPods(): void
    {
        $definitions = [
            'job1' => [
                'pods' => [
                ]
            ]
        ];
        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->never())->method('addJob');

        $workspace = $this->createStub(JobWorkspaceInterface::class);
        $jobUnit = $this->createStub(JobUnitInterface::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionCode(400);
        $builder->compile(
            $definitions,
            $compiledDeployment,
            $workspace,
            $jobUnit,
            $this->createStub(ResourceManager::class),
            $this->createStub(DefaultsBag::class),
        );
    }

    public function testCompileWithoutSchedulingAndPlannedToBeScheduled(): void
    {
        $definitions = [
            'job1' => [
                'pods' => [
                    'foo' => []
                ],
                'planning' => Planning::Scheduled->value,
                'completions' => [
                    'success-on' => [0],
                    'fail-on' => [1],
                    'time-limit' => 10,
                    'shelf-life' => 20,
                ]
            ]
        ];
        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->never())->method('addJob');

        $workspace = $this->createStub(JobWorkspaceInterface::class);
        $jobUnit = $this->createStub(JobUnitInterface::class);

        $this->getPodCompiler()
            ->method('processSetOfPods')
            ->willReturnCallback(
                function (
                    #[SensitiveParameter] array &$definitions,
                    CompiledDeploymentInterface $compiledDeployment,
                    #[SensitiveParameter] JobWorkspaceInterface $workspace,
                    #[SensitiveParameter] JobUnitInterface $job,
                    ResourceManager $resourceManager,
                    DefaultsBag $defaultsBag,
                    PromiseInterface $promise,
                ): (PodCompiler&MockObject)|(PodCompiler&Stub) {
                    $pod = $this->createStub(Pod::class);
                    $pod->method('getName')->willReturn('foo');
                    $promise->success($pod);
                    return $this->getPodCompiler();
                }
            );

        $this->expectException(DomainException::class);
        $this->expectExceptionCode(400);
        $builder->compile(
            $definitions,
            $compiledDeployment,
            $workspace,
            $jobUnit,
            $this->createStub(ResourceManager::class),
            $this->createStub(DefaultsBag::class),
        );
    }

    public function testCompileWithSchedulingAndPlannedToBeStartOnDeployment(): void
    {
        $definitions = [
            'job1' => [
                'pods' => [
                    'foo' => []
                ],
                'planning' => Planning::DuringDeployment->value,
                'schedule' => '*/5 * * * *',
                'completions' => [
                    'success-on' => [0],
                    'fail-on' => [1],
                    'time-limit' => 10,
                    'shelf-life' => 20,
                ]
            ]
        ];
        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->never())->method('addJob');

        $workspace = $this->createStub(JobWorkspaceInterface::class);
        $jobUnit = $this->createStub(JobUnitInterface::class);

        $this->getPodCompiler()
            ->method('processSetOfPods')
            ->willReturnCallback(
                function (
                    #[SensitiveParameter] array &$definitions,
                    CompiledDeploymentInterface $compiledDeployment,
                    #[SensitiveParameter] JobWorkspaceInterface $workspace,
                    #[SensitiveParameter] JobUnitInterface $job,
                    ResourceManager $resourceManager,
                    DefaultsBag $defaultsBag,
                    PromiseInterface $promise,
                ): (PodCompiler&MockObject)|(PodCompiler&Stub) {
                    $pod = $this->createStub(Pod::class);
                    $pod->method('getName')->willReturn('foo');
                    $promise->success($pod);
                    return $this->getPodCompiler();
                }
            );

        $this->expectException(DomainException::class);
        $this->expectExceptionCode(400);
        $builder->compile(
            $definitions,
            $compiledDeployment,
            $workspace,
            $jobUnit,
            $this->createStub(ResourceManager::class),
            $this->createStub(DefaultsBag::class),
        );
    }

    public function testCompileWithoutDefinitions(): void
    {
        $definitions = [];

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->never())->method('addJob');

        $this->getPodCompiler()->expects($this->never())->method('processSetOfPods');

        $this->assertInstanceOf(JobCompiler::class, $this->buildCompiler()->compile(
            $definitions,
            $compiledDeployment,
            $this->createStub(JobWorkspaceInterface::class),
            $this->createStub(JobUnitInterface::class),
            $this->createStub(ResourceManager::class),
            $this->createStub(DefaultsBag::class),
        ));
    }

    public function testCompile(): void
    {
        $definitions = $this->getDefinitionsArray();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->exactly(4))->method('addJob');

        $workspace = $this->createStub(JobWorkspaceInterface::class);
        $jobUnit = $this->createStub(JobUnitInterface::class);

        $this->getPodCompiler()
            ->expects($this->exactly(4))
            ->method('processSetOfPods')
            ->willReturnCallback(
                function (
                    #[SensitiveParameter] array &$definitions,
                    CompiledDeploymentInterface $compiledDeployment,
                    #[SensitiveParameter] JobWorkspaceInterface $workspace,
                    #[SensitiveParameter] JobUnitInterface $job,
                    ResourceManager $resourceManager,
                    DefaultsBag $defaultsBag,
                    PromiseInterface $promise,
                ): (PodCompiler&MockObject)|(PodCompiler&Stub) {
                    $pod = $this->createStub(Pod::class);
                    $pod->method('getName')->willReturn('foo');
                    $promise->success($pod);
                    return $this->getPodCompiler();
                }
            );

        $builder = $this->buildCompiler();
        $this->assertInstanceOf(JobCompiler::class, $builder->compile(
            $definitions,
            $compiledDeployment,
            $workspace,
            $jobUnit,
            $this->createStub(ResourceManager::class),
            $this->createStub(DefaultsBag::class),
        ));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function validSchedulesProvider(): array
    {
        return [
            'every 5 minutes' => ['*/5 * * * *'],
            'every 3 hours' => ['0 */3 * * *'],
            'daily' => ['17 3 * * *'],
            'yearly' => ['0 0 1 1 *'],
            'ranges and names' => ['0 9-17 * * MON-FRI'],
            'lower case names' => ['0 0 * * sun'],
            'lists and question mark' => ['15,45 * ? JAN,jul *'],
            'value and step' => ['5/15 * * * *'],
            'range and step' => ['0 0 1-31/2 * *'],
            'upper bounds' => ['59 23 31 12 6'],
            'descriptor @yearly' => ['@yearly'],
            'descriptor @annually' => ['@annually'],
            'descriptor @monthly' => ['@monthly'],
            'descriptor @weekly' => ['@weekly'],
            'descriptor @daily' => ['@daily'],
            'descriptor @midnight' => ['@midnight'],
            'descriptor @hourly' => ['@hourly'],
            'every duration' => ['@every 1h'],
            'every composed duration' => ['@every 1h30m'],
            'every decimal duration' => ['@every 1.5h'],
        ];
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidSchedulesProvider(): array
    {
        return [
            'six fields' => ['0 0 /3 * * *'],
            'no space' => ['**'],
            'four fields' => ['* * * *'],
            'step without base' => ['/3 * * * *'],
            'minute too high' => ['60 * * * *'],
            'hour too high' => ['* 24 * * *'],
            'day of month too low' => ['* * 0 * *'],
            'day of month too high' => ['* * 32 * *'],
            'month too high' => ['* * * 13 *'],
            'day of week too high' => ['* * * * 7'],
            'reversed range' => ['5-1 * * * *'],
            'null step' => ['*/0 * * * *'],
            'empty item' => ['1,,2 * * * *'],
            'too many hyphens' => ['1-2-3 * * * *'],
            'too many slashes' => ['*/5/2 * * * *'],
            'unknown name' => ['* * * FOO *'],
            'name in a numeric field' => ['MON * * * *'],
            'letters' => ['a b c d e'],
            'cron time zone' => ['CRON_TZ=Europe/Paris 0 3 * * *'],
            'time zone' => ['TZ=UTC 0 3 * * *'],
            'unknown descriptor' => ['@reboot'],
            'upper case descriptor' => ['@DAILY'],
            'every without duration' => ['@every'],
            'every with bad duration' => ['@every foo'],
            'not a string' => [5],
        ];
    }

    private function prepareCompilationOfOneJob(): void
    {
        $this->getPodCompiler(true)
            ->method('processSetOfPods')
            ->willReturnCallback(
                function (
                    #[SensitiveParameter] array &$definitions,
                    CompiledDeploymentInterface $compiledDeployment,
                    #[SensitiveParameter] JobWorkspaceInterface $workspace,
                    #[SensitiveParameter] JobUnitInterface $job,
                    ResourceManager $resourceManager,
                    DefaultsBag $defaultsBag,
                    PromiseInterface $promise,
                ): (PodCompiler&MockObject)|(PodCompiler&Stub) {
                    $pod = $this->createStub(Pod::class);
                    $pod->method('getName')->willReturn('foo');
                    $promise->success($pod);
                    return $this->getPodCompiler();
                }
            );
    }

    #[DataProvider('validSchedulesProvider')]
    public function testCompileWithValidSchedule(string $schedule): void
    {
        $definitions = [
            'backup' => [
                'pods' => [
                    'foo' => []
                ],
                'planning' => Planning::Scheduled->value,
                'schedule' => $schedule,
            ]
        ];

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->once())
            ->method('addJob')
            ->with(
                'backup',
                $this->callback(
                    static fn (Job $job): bool => $schedule === $job->getPlanningSchedule()
                        && Planning::Scheduled === $job->getPlanning()
                ),
            );

        $this->prepareCompilationOfOneJob();

        $this->assertInstanceOf(JobCompiler::class, $this->buildCompiler()->compile(
            $definitions,
            $compiledDeployment,
            $this->createStub(JobWorkspaceInterface::class),
            $this->createStub(JobUnitInterface::class),
            $this->createStub(ResourceManager::class),
            $this->createStub(DefaultsBag::class),
        ));
    }

    #[DataProvider('invalidSchedulesProvider')]
    public function testCompileWithInvalidSchedule(mixed $schedule): void
    {
        $definitions = [
            'backup' => [
                'pods' => [
                    'foo' => []
                ],
                'planning' => Planning::Scheduled->value,
                'schedule' => $schedule,
            ]
        ];

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->never())->method('addJob');

        $this->prepareCompilationOfOneJob();

        $this->expectException(DomainException::class);
        $this->expectExceptionCode(400);
        $this->buildCompiler()->compile(
            $definitions,
            $compiledDeployment,
            $this->createStub(JobWorkspaceInterface::class),
            $this->createStub(JobUnitInterface::class),
            $this->createStub(ResourceManager::class),
            $this->createStub(DefaultsBag::class),
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private function compileOneJob(array $config, CompiledDeploymentInterface $compiledDeployment): JobCompiler
    {
        $definitions = [
            'backup' => [
                'pods' => [
                    'foo' => []
                ],
                ...$config,
            ]
        ];

        $this->prepareCompilationOfOneJob();

        return $this->buildCompiler()->compile(
            $definitions,
            $compiledDeployment,
            $this->createStub(JobWorkspaceInterface::class),
            $this->createStub(JobUnitInterface::class),
            $this->createStub(ResourceManager::class),
            $this->createStub(DefaultsBag::class),
        );
    }

    public function testCompileWithScheduleOptions(): void
    {
        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->once())
            ->method('addJob')
            ->with(
                'backup',
                $this->callback(
                    fn (Job $job): bool => $job->getScheduleOptions() == new ScheduleOptions(
                        timeZone: 'Europe/Paris',
                        concurrency: ConcurrencyPolicy::Forbid,
                        startingDeadline: 300,
                        successfulHistory: 3,
                        failedHistory: 0,
                        suspend: false,
                    )
                ),
            );

        $this->assertInstanceOf(
            JobCompiler::class,
            $this->compileOneJob(
                [
                    'planning' => Planning::Scheduled->value,
                    'schedule' => '17 3 * * *',
                    'schedule-options' => [
                        'time-zone' => 'Europe/Paris',
                        'concurrency' => 'forbid',
                        'starting-deadline' => 300,
                        'successful-history' => 3,
                        'failed-history' => 0,
                        'suspend' => false,
                    ],
                ],
                $compiledDeployment,
            ),
        );
    }

    public function testCompileWithPartialScheduleOptions(): void
    {
        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->once())
            ->method('addJob')
            ->with(
                'backup',
                $this->callback(
                    fn (Job $job): bool => $job->getScheduleOptions() == new ScheduleOptions(
                        concurrency: ConcurrencyPolicy::Replace,
                    )
                ),
            );

        $this->assertInstanceOf(
            JobCompiler::class,
            $this->compileOneJob(
                [
                    'schedule' => '17 3 * * *',
                    'schedule-options' => [
                        'concurrency' => 'replace',
                    ],
                ],
                $compiledDeployment,
            ),
        );
    }

    public function testCompileWithoutScheduleOptions(): void
    {
        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->once())
            ->method('addJob')
            ->with(
                'backup',
                $this->callback(fn (Job $job): bool => null === $job->getScheduleOptions()),
            );

        $this->assertInstanceOf(
            JobCompiler::class,
            $this->compileOneJob(
                [
                    'planning' => Planning::Scheduled->value,
                    'schedule' => '17 3 * * *',
                ],
                $compiledDeployment,
            ),
        );
    }

    public function testCompileWithScheduleOptionsOnAJobDuringDeployment(): void
    {
        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->never())->method('addJob');

        $this->expectException(DomainException::class);
        $this->expectExceptionCode(400);
        $this->compileOneJob(
            [
                'planning' => Planning::DuringDeployment->value,
                'schedule-options' => [
                    'concurrency' => 'forbid',
                ],
            ],
            $compiledDeployment,
        );
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidScheduleOptionsProvider(): array
    {
        return [
            'not an array' => ['forbid'],
            'unknown option' => [['foo' => 'bar']],
            'time zone as offset' => [['time-zone' => '+02:00']],
            'time zone as abbreviation' => [['time-zone' => 'CEST']],
            'unknown time zone' => [['time-zone' => 'Europe/Nowhere']],
            'local time zone' => [['time-zone' => 'Local']],
            'time zone not a string' => [['time-zone' => 2]],
            'unknown concurrency' => [['concurrency' => 'foo']],
            'concurrency not a string' => [['concurrency' => 1]],
            'negative starting deadline' => [['starting-deadline' => -1]],
            'starting deadline not an integer' => [['starting-deadline' => '300']],
            'negative successful history' => [['successful-history' => -1]],
            'successful history not an integer' => [['successful-history' => 1.5]],
            'negative failed history' => [['failed-history' => -1]],
            'suspend not a boolean' => [['suspend' => 'yes']],
        ];
    }

    #[DataProvider('invalidScheduleOptionsProvider')]
    public function testCompileWithInvalidScheduleOptions(mixed $options): void
    {
        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->never())->method('addJob');

        $this->expectException(DomainException::class);
        $this->expectExceptionCode(400);
        $this->compileOneJob(
            [
                'planning' => Planning::Scheduled->value,
                'schedule' => '17 3 * * *',
                'schedule-options' => $options,
            ],
            $compiledDeployment,
        );
    }

    public function testCompileWithWrongExtends(): void
    {
        $definitions = [
            'backup' => [
                'extends' => new stdClass(),
                'pods' => [
                    'foo' => [
                        'image' => 'foo',
                    ],
                ],
            ],
        ];
        $builder = $this->buildCompiler();

        $this->expectException(InvalidArgumentException::class);
        $builder->extends(
            $definitions,
        );
    }

    public function testCompileWithNonExistantExtends(): void
    {
        $definitions = [
            'backup' => [
                'extends' => 'other',
                'pods' => [
                    'foo' => [
                        'image' => 'foo',
                    ],
                ],
            ],
        ];

        $builder = $this->buildCompiler();

        $this->expectException(DomainException::class);
        $builder->extends(
            $definitions,
        );
    }

    public function testCompileWithExtends(): void
    {
        $definitions = [
            'backup' => [
                'extends' => 'foo-ext',
                'pods' => [
                    'foo' => [
                        'image' => 'foo',
                    ],
                ],
            ],
        ];
        $builder = $this->buildCompiler();

        $this->assertInstanceOf(JobCompiler::class, $builder->extends(
            $definitions,
        ));

        $this->assertEquals($definitions, [
            'backup' => [
                'extends' => 'foo-ext',
                'is-parallel' => true,
                'completions' => [
                    'count' => 2,
                    'time-limit' => 10,
                    'shelf-life' => 20,
                ],
                'pods' => [
                    'foo' => [
                        'image' => 'foo',
                    ],
                ],
            ],
        ]);
    }
}
