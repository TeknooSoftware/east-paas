<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
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
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Compilation\Compiler;

use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
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
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(JobCompiler::class)]
class JobCompilerTest extends TestCase
{
    private (PodCompiler&MockObject)|null $podCompiler = null;

    private function getPodCompiler(): PodCompiler&MockObject
    {
        if (null === $this->podCompiler) {
            $this->podCompiler = $this->createMock(PodCompiler::class);
        }

        return $this->podCompiler;
    }

    public function buildCompiler(): JobCompiler
    {
        return new JobCompiler(
            $this->getPodCompiler(),
            [
                'foo-ext' => [
                    'is-parallel' => true,
                    'completions' => [
                        'count' => 2,
                        'time-limit' => 10,
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
                ]
            ],
            'job2' => [
                'pods' => [
                    'foo' => []
                ],
                'completions' => [
                    'mode' => CompletionMode::Indexed->value,
                    'count' => 2,
                ],
                'schedule' => '**'
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
                    'time-limit' => 10
                ]
            ],
            'job4' => [
                'pods' => [
                    'foo' => []
                ],
                'planning' => Planning::DuringDeployment->value,
            ]
        ];
    }

    public function testCompileWithoutPods()
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

        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionCode(400);
        $builder->compile(
            $definitions,
            $compiledDeployment,
            $workspace,
            $jobUnit,
            $this->createMock(ResourceManager::class),
            $this->createMock(DefaultsBag::class),
        );
    }

    public function testCompileWithoutSchedulingAndPlannedToBeScheduled()
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
                    'time-limit' => 10
                ]
            ]
        ];
        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->never())->method('addJob');

        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class);

        $this->getPodCompiler()
            ->expects($this->any())
            ->method('processSetOfPods')
            ->willReturnCallback(
                function (
                    #[SensitiveParameter] array &$definitions,
                    CompiledDeploymentInterface $compiledDeployment,
                    #[SensitiveParameter] JobUnitInterface $job,
                    ResourceManager $resourceManager,
                    DefaultsBag $defaultsBag,
                    PromiseInterface $promise,
                ) {
                    $pod = $this->createMock(Pod::class);
                    $pod->expects($this->any())->method('getName')->willReturn('foo');
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
            $this->createMock(ResourceManager::class),
            $this->createMock(DefaultsBag::class),
        );
    }

    public function testCompileWithSchedulingAndPlannedToBeStartOnDeployment()
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
                    'time-limit' => 10
                ]
            ]
        ];
        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->never())->method('addJob');

        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class);

        $this->getPodCompiler()
            ->expects($this->any())
            ->method('processSetOfPods')
            ->willReturnCallback(
                function (
                    #[SensitiveParameter] array &$definitions,
                    CompiledDeploymentInterface $compiledDeployment,
                    #[SensitiveParameter] JobUnitInterface $job,
                    ResourceManager $resourceManager,
                    DefaultsBag $defaultsBag,
                    PromiseInterface $promise,
                ) {
                    $pod = $this->createMock(Pod::class);
                    $pod->expects($this->any())->method('getName')->willReturn('foo');
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
            $this->createMock(ResourceManager::class),
            $this->createMock(DefaultsBag::class),
        );
    }

    public function testCompileWithoutDefinitions()
    {
        $definitions = [];

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->never())->method('addJob');

        $this->getPodCompiler()->expects($this->never())->method('processSetOfPods');

        self::assertInstanceOf(
            JobCompiler::class,
            $this->buildCompiler()->compile(
                $definitions,
                $compiledDeployment,
                $this->createMock(JobWorkspaceInterface::class),
                $this->createMock(JobUnitInterface::class),
                $this->createMock(ResourceManager::class),
                $this->createMock(DefaultsBag::class),
            )
        );
    }

    public function testCompile()
    {
        $definitions = $this->getDefinitionsArray();
        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->exactly(4))->method('addJob');

        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class);

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
                ) {
                    $pod = $this->createMock(Pod::class);
                    $pod->expects($this->any())->method('getName')->willReturn('foo');
                    $promise->success($pod);
                    return $this->getPodCompiler();
                }
            );

        self::assertInstanceOf(
            JobCompiler::class,
            $builder->compile(
                $definitions,
                $compiledDeployment,
                $workspace,
                $jobUnit,
                $this->createMock(ResourceManager::class),
                $this->createMock(DefaultsBag::class),
            )
        );
    }

    public function testCompileWithWrongExtends()
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

    public function testCompileWithNonExistantExtends()
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

    public function testCompileWithExtends()
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

        self::assertInstanceOf(
            JobCompiler::class,
            $builder->extends(
                $definitions,
            )
        );

        self::assertEquals(
            $definitions,
            [
                'backup' => [
                    'extends' => 'foo-ext',
                    'is-parallel' => true,
                    'completions' => [
                        'count' => 2,
                        'time-limit' => 10,
                    ],
                    'pods' => [
                        'foo' => [
                            'image' => 'foo',
                        ],
                    ],
                ],
            ]
        );
    }
}
