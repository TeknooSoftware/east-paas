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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use stdClass;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\CompletionMode;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\Planning;
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
                'schedule' => '**',
                'shelf-life' => null,
            ],
            'job3' => [
                'pods' => [
                    'foo' => []
                ],
                'planning' => Planning::Scheduled->value,
                'schedule' => '**',
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
                'schedule' => '**',
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
