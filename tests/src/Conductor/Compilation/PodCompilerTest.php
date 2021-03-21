<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Conductor\Compilation;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Conductor\Compilation\PodCompiler;
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Conductor\Compilation\PodCompiler
 */
class PodCompilerTest extends TestCase
{
    public function buildCompiler(): PodCompiler
    {
        return new PodCompiler();
    }

    private function getDefinitionsArray(): array
    {
        return [
            'php-pod' => [
                'replicas' => 1,
                'containers' => [
                    'php-react' => [
                        'replicas' => 3,
                        'image' => 'php-react',
                        'version' => 7.4,
                        'listen' => [8080]
                    ],
                    'php-composer' => [
                        'replicas' => 3,
                        'image' => 'registry/lib/php-composer',
                        'variables' => [
                            'from-secrets' => [
                                'bar' => 'myvauult.key',
                            ],
                            'foo' => 'bar'
                        ],
                        'version' => 7.4,
                        'volumes' => [
                            'persistent_volume' => [
                                'persistent' => true,
                                'mount-path' => '/app/persistent/',
                            ],
                            'embedded' => [
                                'add' => [
                                    'foo',
                                    'bar',
                                ],
                                'mount-path' => '/app/embedded/',
                            ],
                            'other_name2' => [
                                'from' => 'main',
                                'mount-path' => '/app/vendor/',
                            ],
                            'vault' => [
                                'from-secret' => 'vault',
                                'mount-path' => '/app/vendor/',
                            ],
                        ]
                    ],
                ],
            ],
        ];
    }

    public function testCompileWithoutDefinitions()
    {
        $definitions = [];

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::never())->method('addPod');

        self::assertInstanceOf(
            PodCompiler::class,
            $this->buildCompiler()->compile(
                $definitions,
                $compiledDeployment,
                $this->createMock(JobWorkspaceInterface::class),
                $this->createMock(JobUnitInterface::class )
            )
        );
    }

    public function testCompile()
    {
        $definitions = $this->getDefinitionsArray();
        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::exactly(1))->method('addPod');

        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class );

        self::assertInstanceOf(
            PodCompiler::class,
            $builder->compile(
                $definitions,
                $compiledDeployment,
                $workspace,
                $jobUnit,
                'fooBar'
            )
        );
    }

    public function testCompileWithInvalidVolume()
    {
        $definitions = $this->getDefinitionsArray();
        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::never())->method('addPod');
        $compiledDeployment->expects(self::any())
            ->method('importVolume')
            ->willReturnCallback(
                function (string $volumeFrom, string $mountPath, PromiseInterface $promise) use ($compiledDeployment) {
                    $promise->fail(new \DomainException('foo'));

                    return $compiledDeployment;
                }
            );

        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class );

        $this->expectException(\DomainException::class);

        self::assertInstanceOf(
            PodCompiler::class,
            $builder->compile(
                $definitions,
                $compiledDeployment,
                $workspace,
                $jobUnit,
                'fooBar'
            )
        );
    }

    public function testCompileWithoutStorageIdentifierInVolume()
    {
        $definitions = $this->getDefinitionsArray();
        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::never())->method('addPod');
        $compiledDeployment->expects(self::any())
            ->method('importVolume')
            ->willReturnCallback(
                function (string $volumeFrom, string $mountPath, PromiseInterface $promise) use ($compiledDeployment) {
                    $promise->fail(new \DomainException('foo'));

                    return $compiledDeployment;
                }
            );

        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class );

        $this->expectException(\RuntimeException::class);

        self::assertInstanceOf(
            PodCompiler::class,
            $builder->compile(
                $definitions,
                $compiledDeployment,
                $workspace,
                $jobUnit
            )
        );
    }

    public function testCompileDeploymentWithVolumeWithoutMountPath()
    {
        $this->expectException(\RuntimeException::class);

        $definitions = $this->getDefinitionsArray();
        unset($definitions['php-pod']['containers']['php-composer']['volumes']['embedded']['mount-path']);
        unset($definitions['php-pod']['containers']['php-composer']['volumes']['persistent_volume']['mount-path']);
        unset($definitions['php-pod']['containers']['php-composer']['volumes']['other_name2']['mount-path']);
        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::any())->method('addPod');

        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $jobUnit = $this->createMock(JobUnitInterface::class );

        self::assertInstanceOf(
            PodCompiler::class,
            $builder->compile(
                $definitions,
                $compiledDeployment,
                $workspace,
                $jobUnit
            )
        );
    }
}
