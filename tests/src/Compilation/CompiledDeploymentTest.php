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

namespace Teknoo\Tests\East\Paas\Compilation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Teknoo\East\Paas\Compilation\CompiledDeployment;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Container;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Ingress;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Service;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheck;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\Image;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\Planning;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Map;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\ResourceSet;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Secret;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\PersistentVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuildableInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\VolumeInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use TypeError;

use function current;
use function strpos;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(CompiledDeployment::class)]
class CompiledDeploymentTest extends TestCase
{
    private function buildObject(): CompiledDeployment
    {
        return new CompiledDeployment(1, 'prefix', 'project');
    }

    public function testGetVersion(): void
    {
        $this->assertEquals(1, $this->buildObject()->getVersion());
    }

    public function testSetDefaultBagsWrongBuildable(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->setDefaultBags(new stdClass());
    }

    public function testSetDefaultBags(): void
    {
        $this->assertInstanceOf(CompiledDeployment::class, $this->buildObject()->setDefaultBags(
            $this->createStub(DefaultsBag::class)
        ));
    }

    public function testAddBuildableWrongBuildable(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->addBuildable(new stdClass());
    }

    public function testAddBuildable(): void
    {
        $this->assertInstanceOf(CompiledDeployment::class, $this->buildObject()->addBuildable(
            $this->createStub(BuildableInterface::class)
        ));
    }

    public function testupdateBuildableWrongOldBuildable(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->updateBuildable(
            new stdClass(),
            $this->createStub(BuildableInterface::class)
        );
    }

    public function testupdateBuildableWrongNewBuildable(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->updateBuildable(
            $this->createStub(BuildableInterface::class),
            new stdClass()
        );
    }

    public function testupdateBuildable(): void
    {
        $this->assertInstanceOf(CompiledDeployment::class, $this->buildObject()->updateBuildable(
            $this->createStub(BuildableInterface::class),
            $this->createStub(BuildableInterface::class)
        ));
    }

    public function testAddHookWrongHook(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->addHook('foo', new stdClass());
    }

    public function testAddHookWrongHookName(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->addHook(new stdClass(), $this->createStub(HookInterface::class));
    }

    public function testAddHook(): void
    {
        $this->assertInstanceOf(CompiledDeployment::class, $this->buildObject()->addHook(
            'foo',
            $this->createStub(HookInterface::class)
        ));
    }

    public function testAddVolumeWrongVolume(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->addVolume('foo', new stdClass());
    }

    public function testAddVolumeWrongVolumeName(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->addVolume(new stdClass(), $this->createStub(Volume::class));
    }

    public function testAddVolume(): void
    {
        $this->assertInstanceOf(CompiledDeployment::class, $this->buildObject()->addVolume(
            'foo',
            $this->createStub(Volume::class)
        ));
    }

    public function testImportVolumeWrongVolumeFrom(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->importVolume(
            new stdClass(),
            'foo',
            $this->createStub(PromiseInterface::class)
        );
    }

    public function testImportVolumeWrongMountPath(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->importVolume(
            'foo',
            new stdClass(),
            $this->createStub(PromiseInterface::class)
        );
    }

    public function testImportVolumeWrongProise(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->importVolume(
            'foo',
            'bar',
            new stdClass()
        );
    }

    public function testImportVolume(): void
    {
        $volume = $this->createMock(Volume::class);
        $volume->expects($this->once())->method('import')->with('/bar')->willReturnSelf();

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success')->with($volume);
        $promise->expects($this->never())->method('fail');

        $object = $this->buildObject();
        $object->addVolume('foo', $volume);

        $this->assertInstanceOf(CompiledDeployment::class, $object->importVolume(
            'foo',
            '/bar',
            $promise
        ));
    }

    public function testImportVolumeNotFound(): void
    {
        $volume = $this->createMock(Volume::class);
        $volume->expects($this->never())->method('import');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success')->with($volume);
        $promise->expects($this->once())->method('fail');

        $object = $this->buildObject();
        $object->addVolume('bar', $volume);

        $this->assertInstanceOf(CompiledDeployment::class, $object->importVolume(
            'foo',
            '/bar',
            $promise
        ));
    }

    public function testImportVolumeBadType(): void
    {
        $volume = $this->createStub(VolumeInterface::class);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success')->with($volume);
        $promise->expects($this->once())->method('fail');

        $object = $this->buildObject();
        $object->addVolume('foo', $volume);

        $this->assertInstanceOf(CompiledDeployment::class, $object->importVolume(
            'foo',
            '/bar',
            $promise
        ));
    }

    public function testAddServiceWrongService(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->addService('foo', new stdClass());
    }

    public function testAddServiceWrongServiceName(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->addService(new stdClass(), $this->createStub(Service::class));
    }

    public function testAddService(): void
    {
        $this->assertInstanceOf(CompiledDeployment::class, $this->buildObject()->addService(
            'foo',
            $this->createStub(Service::class)
        ));
    }

    public function testAddSecretWrongSecret(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->addSecret('foo', new stdClass());
    }

    public function testAddSecretWrongSecretName(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->addSecret(new stdClass(), $this->createStub(Secret::class));
    }

    public function testAddSecret(): void
    {
        $this->assertInstanceOf(CompiledDeployment::class, $this->buildObject()->addSecret(
            'foo',
            $this->createStub(Secret::class)
        ));
    }

    public function testAddMapWrongMap(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->addMap('foo', new stdClass());
    }

    public function testAddMapWrongMapName(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->addMap(new stdClass(), $this->createStub(Map::class));
    }

    public function testAddMap(): void
    {
        $this->assertInstanceOf(CompiledDeployment::class, $this->buildObject()->addMap(
            'foo',
            $this->createStub(Map::class)
        ));
    }

    public function testAddIngressWrongIngress(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->addIngress('foo', new stdClass());
    }

    public function testAddIngressWrongIngressName(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->addIngress(new stdClass(), $this->createStub(Ingress::class));
    }

    public function testAddIngress(): void
    {
        $this->assertInstanceOf(CompiledDeployment::class, $this->buildObject()->addIngress(
            'foo',
            $this->createStub(Ingress::class)
        ));
    }

    public function testAddPodWrongContainer(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->addPod('foo', new stdClass());
    }

    public function testAddPodWrongContainerName(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->addPod(new stdClass(), $this->createStub(Pod::class));
    }

    public function testAddPod(): void
    {
        $this->assertInstanceOf(CompiledDeployment::class, $this->buildObject()
            ->addBuildable(
                (new Image('foo', 'bar', false, '1.2', ['foo' => 'bar']))
            )
            ->addPod(
                'bar',
                new Pod('bar', 1, [
                    new Container(
                        'bar',
                        'foo',
                        '1.2',
                        [80],
                        [],
                        [],
                        $this->createStub(CompiledDeployment\HealthCheck::class),
                        $this->createStub(CompiledDeployment\ResourceSet::class)
                    )
                ])
            )
            ->addPod(
                'bar',
                new Pod('bar', 1, [
                    new Container(
                        'bar',
                        'registry/foo',
                        '1.2',
                        [80],
                        [],
                        [],
                        $this->createStub(CompiledDeployment\HealthCheck::class),
                        $this->createStub(CompiledDeployment\ResourceSet::class)
                    )
                ])
            ));
    }

    public function testForeachHookBadCallback(): void
    {
        $this->expectException(TypeError::class);

        $this->buildObject()->foreachHook(new stdClass());
    }

    public function testForeachHook(): void
    {
        $cd = $this->buildObject();

        $cd->addHook(
            'foo1',
            $this->createStub(HookInterface::class)
        );

        $cd->addHook(
            'foo2',
            $this->createStub(HookInterface::class)
        );

        $count = 0;
        $this->assertInstanceOf(CompiledDeployment::class, $cd->foreachHook(function ($hook) use (&$count): void {
            $this->assertInstanceOf(HookInterface::class, $hook);

            ++$count;
        }));

        $this->assertEquals(2, $count);
    }

    public function testForeachVolumeBadCallback(): void
    {
        $this->expectException(TypeError::class);

        $this->buildObject()->foreachVolume(new stdClass());
    }

    public function testForeachVolumeOnlyWithPod(): void
    {
        $cd = $this->buildObject();

        $cd->addVolume(
            'foo1',
            new Volume('foo1', [], 'bar', '/mount')
        );

        $cd->addVolume(
            'foo2',
            new Volume('foo2', [], 'bar', '/mount')
        );

        $cd->addBuildable(
            $img = (new Image('foo', 'bar', false, '1.2', ['foo' => 'bar']))
        );

        $cd->addPod(
            'foo1',
            new Pod(
                'foo1',
                1,
                [
                    new Container(
                        'foo1',
                        'foo',
                        '1.2',
                        [80],
                        [
                            'foo3' => new Volume('foo3', [], 'bar', '/mount')
                        ],
                        [],
                        $this->createStub(CompiledDeployment\HealthCheck::class),
                        $this->createStub(CompiledDeployment\ResourceSet::class),
                    )
                ]
            )
        );

        $count = 0;
        $this->assertInstanceOf(CompiledDeployment::class, $cd->foreachVolume(function ($name, $volume) use (&$count): void {
            $this->assertEquals(0, strpos($name, 'foo'));
            $this->assertInstanceOf(Volume::class, $volume);

            ++$count;
        }));

        $this->assertEquals(3, $count);
    }

    public function testForeachVolumeOnlyWithJob(): void
    {
        $cd = $this->buildObject();

        $cd->addVolume(
            'foo1',
            new Volume('foo1', [], 'bar', '/mount')
        );

        $cd->addVolume(
            'foo2',
            new Volume('foo2', [], 'bar', '/mount')
        );

        $cd->addBuildable(
            $img = (new Image('foo', 'bar', false, '1.2', ['foo' => 'bar']))
        );

        $cd->addJob(
            'foo1',
            new Job(
                name: 'foo1',
                pods: [
                    'foo1' => new Pod(
                        'foo1',
                        1,
                        [
                            new Container(
                                'foo1',
                                'foo',
                                '1.2',
                                [80],
                                [
                                    'foo3' => new Volume('foo3', [], 'bar', '/mount')
                                ],
                                [],
                                $this->createStub(CompiledDeployment\HealthCheck::class),
                                $this->createStub(CompiledDeployment\ResourceSet::class),
                            )
                        ]
                    )
                ],
                completionsCount: 1,
                isParallel: false,
                completion: Job\CompletionMode::Common,
                successCondition: null,
                timeLimit: 10,
                planning: Planning::DuringDeployment,
                planningSchedule: null,
            )
        );

        $cd->addPod(
            'foo2',
            new Pod(
                'foo2',
                1,
                [
                    new Container(
                        'foo2',
                        'foo',
                        '1.2',
                        [80],
                        [
                            'foo4' => new Volume('foo4', [], 'bar', '/mount')
                        ],
                        [],
                        $this->createStub(CompiledDeployment\HealthCheck::class),
                        $this->createStub(CompiledDeployment\ResourceSet::class),
                    )
                ]
            )
        );

        $count = 0;
        $this->assertInstanceOf(CompiledDeployment::class, $cd->foreachVolume(function ($name, $volume) use (&$count): void {
            $this->assertEquals(0, strpos($name, 'foo'));
            $this->assertInstanceOf(Volume::class, $volume);

            ++$count;
        }));

        $this->assertEquals(4, $count);
    }

    public function testForeachVolumeWithPodAndJob(): void
    {
        $cd = $this->buildObject();

        $cd->addVolume(
            'foo1',
            new Volume('foo1', [], 'bar', '/mount')
        );

        $cd->addVolume(
            'foo2',
            new Volume('foo2', [], 'bar', '/mount')
        );

        $cd->addBuildable(
            $img = (new Image('foo', 'bar', false, '1.2', ['foo' => 'bar']))
        );

        $cd->addJob(
            'foo1',
            new Job(
                name: 'foo1',
                pods: [
                    'foo1' => new Pod(
                        'foo1',
                        1,
                        [
                            new Container(
                                'foo1',
                                'foo',
                                '1.2',
                                [80],
                                [
                                    'foo3' => new Volume('foo3', [], 'bar', '/mount')
                                ],
                                [],
                                $this->createStub(CompiledDeployment\HealthCheck::class),
                                $this->createStub(CompiledDeployment\ResourceSet::class),
                            )
                        ]
                    )
                ],
                completionsCount: 1,
                isParallel: false,
                completion: Job\CompletionMode::Common,
                successCondition: null,
                timeLimit: 10,
                planning: Planning::DuringDeployment,
                planningSchedule: null,
            )
        );

        $count = 0;
        $this->assertInstanceOf(CompiledDeployment::class, $cd->foreachVolume(function ($name, $volume) use (&$count): void {
            $this->assertEquals(0, strpos($name, 'foo'));
            $this->assertInstanceOf(Volume::class, $volume);

            ++$count;
        }));

        $this->assertEquals(3, $count);
    }

    public function testForeachBuildableBadCallback(): void
    {
        $this->expectException(TypeError::class);

        $this->buildObject()->foreachBuildable(new stdClass());
    }

    public function testForeachBuildable(): void
    {
        $cd = $this->buildObject();

        $cd->addBuildable(
            (new Image('foo', 'bar', false, '1.2', ['foo' => 'bar']))
        );

        $cd->addBuildable(
            (new Image('bar', 'foo', false, '1.2', ['foo' => 'bar']))
        );

        $cd->addBuildable(
            (new Image('hello', 'world', false, '1.2', ['foo' => 'bar']))
        );

        $cd->addPod(
            'foo1',
            new Pod('foo1', 1, [
                new Container(
                    'foo1',
                    'foo',
                    '1.2',
                    [80],
                    [],
                    [],
                    $this->createStub(CompiledDeployment\HealthCheck::class),
                    $this->createStub(CompiledDeployment\ResourceSet::class),
                )
            ])
        );

        $cd->addPod(
            'foo2',
            new Pod('foo2', 1, [
                new Container(
                    'foo2',
                    'foo',
                    '1.2',
                    [80],
                    [],
                    [],
                    $this->createStub(CompiledDeployment\HealthCheck::class),
                    $this->createStub(CompiledDeployment\ResourceSet::class),
                )
            ])
        );

        $cd->addPod(
            'bar1',
            new Pod(
                'bar1',
                1,
                [
                    new Container(
                        'registry/bar1',
                        'bar',
                        '1.2',
                        [80],
                        [],
                        [],
                        $this->createStub(HealthCheck::class),
                        $this->createStub(ResourceSet::class),
                    )
                ]
            )
        );

        $count = 0;
        $this->assertInstanceOf(CompiledDeployment::class, $cd->foreachBuildable(function ($buildable) use (&$count): void {
            $this->assertInstanceOf(BuildableInterface::class, $buildable);
            $this->assertNotEquals('hello', $buildable->getName());

            ++$count;
        }));

        $this->assertEquals(2, $count);
    }

    public function testForeachPodBadCallback(): void
    {
        $this->expectException(TypeError::class);

        $this->buildObject()->foreachPod(new stdClass());
    }

    public function testForeachPod(): void
    {
        $cd = $this->buildObject();

        $cd->addBuildable(
            $img = (new Image('foo', 'bar', false, '1.2', ['foo' => 'bar']))
        );

        $cd->addBuildable(
            (new Image('bar', 'foo', false, 'latest', ['foo' => 'bar']))
        );

        $cd->addBuildable(
            (new Image('hello', 'world', false, '1.2', ['foo' => 'bar']))
        );

        $cd->addVolume(
            'foo',
            $foo = (new Volume('foo1', [], '/foo', '/mount'))
        );

        $cd->addVolume(
            'bar',
            $bar = (new Volume('bar1', [], '/bar', '/mount'))
        );

        $cd->addPod(
            'foo1',
            new Pod(
                'foo1',
                1,
                [
                    new Container(
                        'foo1',
                        'foo',
                        '1.2',
                        [
                            80
                        ],
                        [
                            'foo' => $foo
                        ],
                        [],
                        $this->createStub(HealthCheck::class),
                        $this->createStub(ResourceSet::class),
                    ),
                    new Container(
                        'foo2',
                        'alpine',
                        '3.16',
                        [
                            80
                        ],
                        [
                            'foo' => $foo
                        ],
                        [],
                        $this->createStub(HealthCheck::class),
                        $this->createStub(ResourceSet::class),
                    )
                ]
            )
        );

        $cd->updateBuildable($img, $img->withRegistry('registry.io'));

        $cd->addPod(
            'foo2',
            new Pod(
                'foo2',
                1,
                [
                    new Container(
                        'foo2',
                        'foo',
                        '1.2',
                        [
                            80
                        ],
                        [
                            'bar' => $bar,
                            'p1' => new PersistentVolume('p1', '/mnt', 'foo', '/mount'),
                        ],
                        [],
                        $this->createStub(HealthCheck::class),
                        $this->createStub(ResourceSet::class),
                    )
                ]
            )
        );

        $cd->addPod(
            'bar1',
            new Pod(
                'bar1',
                1,
                [
                    new Container(
                        'bar1',
                        'bar',
                        null,
                        [
                            80
                        ],
                        [],
                        [],
                        $this->createStub(HealthCheck::class),
                        $this->createStub(ResourceSet::class),
                    )
                ]
            )
        );

        $count = 0;
        $this->assertInstanceOf(CompiledDeployment::class, $cd->foreachPod(function ($pod, $buildables, $volumes) use (&$count): void {
            $this->assertInstanceOf(Pod::class, $pod);
            $this->assertNotEmpty($buildables);
            $this->assertInstanceOf(BuildableInterface::class, current(current($buildables)));

            if ('bar1' === $pod->getName()) {
                $this->assertEmpty($volumes);
            } else {
                $this->assertNotEmpty($volumes);

                $this->assertInstanceOf(Volume::class, current($volumes));
            }

            ++$count;
        }));

        $this->assertEquals(3, $count);
    }

    public function testForeachJobBadCallback(): void
    {
        $this->expectException(TypeError::class);

        $this->buildObject()->foreachJob(new stdClass());
    }

    public function testForeachJob(): void
    {
        $cd = $this->buildObject();

        $cd->addBuildable(
            $img = (new Image('foo', 'bar', false, '1.2', ['foo' => 'bar']))
        );

        $cd->addBuildable(
            (new Image('bar', 'foo', false, 'latest', ['foo' => 'bar']))
        );

        $cd->addBuildable(
            (new Image('hello', 'world', false, '1.2', ['foo' => 'bar']))
        );

        $cd->addVolume(
            'foo',
            $foo = (new Volume('foo1', [], '/foo', '/mount'))
        );

        $cd->addVolume(
            'bar',
            $bar = (new Volume('bar1', [], '/bar', '/mount'))
        );

        $cd->addJob(
            'foo1',
            new Job(
                name: 'foo1',
                pods: [
                    'foo1' => new Pod(
                        'foo1',
                        1,
                        [
                            new Container(
                                'foo1',
                                'foo',
                                '1.2',
                                [
                                    80
                                ],
                                [
                                    'foo' => $foo
                                ],
                                [],
                                $this->createStub(HealthCheck::class),
                                $this->createStub(ResourceSet::class),
                            ),
                            new Container(
                                'foo2',
                                'alpine',
                                '3.16',
                                [
                                    80
                                ],
                                [
                                    'foo' => $foo
                                ],
                                [],
                                $this->createStub(HealthCheck::class),
                                $this->createStub(ResourceSet::class),
                            )
                        ]
                    )
                ],
                completionsCount: 1,
                isParallel: false,
                completion: Job\CompletionMode::Common,
                successCondition: null,
                timeLimit: 10,
                planning: Planning::DuringDeployment,
                planningSchedule: null,
            )
        );

        $cd->updateBuildable($img, $img->withRegistry('registry.io'));

        $cd->addJob(
            'foo2',
            new Job(
                name: 'foo2',
                pods: [
                    'bar1' => new Pod(
                        'bar1',
                        1,
                        [
                            new Container(
                                'bar1',
                                'bar',
                                null,
                                [
                                    80
                                ],
                                [],
                                [],
                                $this->createStub(HealthCheck::class),
                                $this->createStub(ResourceSet::class),
                            )
                        ]
                    ),
                ],
                completionsCount: 1,
                isParallel: false,
                completion: Job\CompletionMode::Common,
                successCondition: null,
                timeLimit: 10,
                planning: Planning::DuringDeployment,
                planningSchedule: null,
            )
        );

        $count = 0;
        $this->assertInstanceOf(CompiledDeployment::class, $cd->foreachJob(function ($job, $buildables, $volumes) use (&$count): void {
            $this->assertInstanceOf(Job::class, $job);
            $this->assertNotEmpty($buildables);
            $this->assertInstanceOf(BuildableInterface::class, current(current($buildables)));

            if ('foo2' === $job->getName()) {
                $this->assertEmpty($volumes);
            } else {
                $this->assertNotEmpty($volumes);

                $this->assertInstanceOf(Volume::class, current($volumes));
            }

            ++$count;
        }));

        $this->assertEquals(2, $count);
    }

    public function testForeachServiceBadCallback(): void
    {
        $this->expectException(TypeError::class);

        $this->buildObject()->foreachService(new stdClass());
    }

    public function testForeachService(): void
    {
        $cd = $this->buildObject();

        $cd->addService(
            'foo1',
            $this->createStub(Service::class)
        );

        $cd->addService(
            'foo2',
            $this->createStub(Service::class)
        );

        $count = 0;
        $this->assertInstanceOf(CompiledDeployment::class, $cd->foreachService(function ($service) use (&$count): void {
            $this->assertInstanceOf(Service::class, $service);

            ++$count;
        }));

        $this->assertEquals(2, $count);
    }

    public function testForeachSecretBadCallback(): void
    {
        $this->expectException(TypeError::class);

        $this->buildObject()->foreachSecret(new stdClass());
    }

    public function testForeachSecret(): void
    {
        $cd = $this->buildObject();

        $cd->addSecret(
            'foo1',
            $this->createStub(Secret::class)
        );

        $cd->addSecret(
            'foo2',
            $this->createStub(Secret::class)
        );

        $count = 0;
        $this->assertInstanceOf(CompiledDeployment::class, $cd->foreachSecret(function ($secret) use (&$count): void {
            $this->assertInstanceOf(Secret::class, $secret);

            ++$count;
        }));

        $this->assertEquals(2, $count);
    }

    public function testForeachMapBadCallback(): void
    {
        $this->expectException(TypeError::class);

        $this->buildObject()->foreachMap(new stdClass());
    }

    public function testForeachMap(): void
    {
        $cd = $this->buildObject();

        $cd->addMap(
            'foo1',
            $this->createStub(Map::class)
        );

        $cd->addMap(
            'foo2',
            $this->createStub(Map::class)
        );

        $count = 0;
        $this->assertInstanceOf(CompiledDeployment::class, $cd->foreachMap(function ($map) use (&$count): void {
            $this->assertInstanceOf(Map::class, $map);

            ++$count;
        }));

        $this->assertEquals(2, $count);
    }

    public function testForeachIngressBadCallback(): void
    {
        $this->expectException(TypeError::class);

        $this->buildObject()->foreachIngress(new stdClass());
    }

    public function testForeachIngress(): void
    {
        $cd = $this->buildObject();

        $cd->addIngress(
            'foo1',
            $this->createStub(Ingress::class)
        );

        $cd->addIngress(
            'foo2',
            $this->createStub(Ingress::class)
        );

        $count = 0;
        $this->assertInstanceOf(CompiledDeployment::class, $cd->foreachIngress(function ($ingress) use (&$count): void {
            $this->assertInstanceOf(Ingress::class, $ingress);

            ++$count;
        }));

        $this->assertEquals(2, $count);
    }

    public function testCompileDefaultsBagsBadCallback(): void
    {
        $this->expectException(TypeError::class);

        $this->buildObject()->compileDefaultsBags(new stdClass());
    }

    public function testCompileDefaultsBags(): void
    {
        $cd = $this->buildObject();

        $cd->setDefaultBags(
            $this->createStub(DefaultsBag::class),
        );

        $count = 0;
        $this->assertInstanceOf(CompiledDeployment::class, $cd->compileDefaultsBags('foo', function ($bag) use (&$count): void {
            $this->assertInstanceOf(DefaultsBag::class, $bag);

            ++$count;
        }));

        $this->assertEquals(1, $count);
    }

    public function testWithJobSettingsBadCallback(): void
    {
        $this->expectException(TypeError::class);

        $this->buildObject()->withJobSettings(new stdClass());
    }

    public function testWithJobSettings(): void
    {
        $cd = $this->buildObject();

        $count = 0;
        $this->assertInstanceOf(CompiledDeployment::class, $cd->withJobSettings(function ($version, $prefix, $projectName) use (&$count): void {
            $this->assertEquals(1, $version);
            $this->assertEquals('prefix', $prefix);
            $this->assertEquals('project', $projectName);

            ++$count;
        }));

        $this->assertEquals(1, $count);
    }
}
