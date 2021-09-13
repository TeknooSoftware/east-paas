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

namespace Teknoo\Tests\East\Paas\Compilation;

use PHPUnit\Framework\TestCase;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Container;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\Image;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Ingress;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\PersistentVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Secret;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Service;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuildableInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\VolumeInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Compilation\CompiledDeployment
 */
class CompiledDeploymentTest extends TestCase
{
    private function buildObject(): CompiledDeployment
    {
        return new CompiledDeployment(1, 'default_namespace');
    }

    public function testAddBuildableWrongBuildable()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->addBuildable(new \stdClass());
    }

    public function testAddBuildable()
    {
        self::assertInstanceOf(
            CompiledDeployment::class,
            $this->buildObject()->addBuildable(
                $this->createMock(BuildableInterface::class)
            )
        );
    }

    public function testupdateBuildableWrongOldBuildable()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->updateBuildable(
            new \stdClass(),
            $this->createMock(BuildableInterface::class)
        );
    }

    public function testupdateBuildableWrongNewBuildable()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->updateBuildable(
            $this->createMock(BuildableInterface::class),
            new \stdClass()
        );
    }

    public function testupdateBuildable()
    {
        self::assertInstanceOf(
            CompiledDeployment::class,
            $this->buildObject()->updateBuildable(
                $this->createMock(BuildableInterface::class),
                $this->createMock(BuildableInterface::class)
            )
        );
    }

    public function testAddHookWrongHook()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->addHook('foo', new \stdClass());
    }

    public function testAddHookWrongHookName()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->addHook(new \stdClass(), $this->createMock(HookInterface::class));
    }

    public function testAddHook()
    {
        self::assertInstanceOf(
            CompiledDeployment::class,
            $this->buildObject()->addHook(
                'foo',
                $this->createMock(HookInterface::class)
            )
        );
    }

    public function testAddVolumeWrongVolume()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->addVolume('foo', new \stdClass());
    }

    public function testAddVolumeWrongVolumeName()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->addVolume(new \stdClass(), $this->createMock(Volume::class));
    }

    public function testAddVolume()
    {
        self::assertInstanceOf(
            CompiledDeployment::class,
            $this->buildObject()->addVolume(
                'foo',
                $this->createMock(Volume::class)
            )
        );
    }

    public function testImportVolumeWrongVolumeFrom()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->importVolume(
            new \stdClass(),
            'foo',
            $this->createMock(PromiseInterface::class)
        );
    }

    public function testImportVolumeWrongMountPath()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->importVolume(
            'foo',
            new \stdClass(),
            $this->createMock(PromiseInterface::class)
        );
    }

    public function testImportVolumeWrongProise()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->importVolume(
            'foo',
            'bar',
            new \stdClass()
        );
    }

    public function testImportVolume()
    {
        $volume = $this->createMock(Volume::class);
        $volume->expects(self::once())->method('import')->with('/bar')->willReturnSelf();

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success')->with($volume);
        $promise->expects(self::never())->method('fail');

        $object = $this->buildObject();
        $object->addVolume('foo', $volume);

        self::assertInstanceOf(
            CompiledDeployment::class,
            $object->importVolume(
                'foo',
                '/bar',
                $promise
            )
        );
    }

    public function testImportVolumeNotFound()
    {
        $volume = $this->createMock(Volume::class);
        $volume->expects(self::never())->method('import');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success')->with($volume);
        $promise->expects(self::once())->method('fail');

        $object = $this->buildObject();
        $object->addVolume('bar', $volume);

        self::assertInstanceOf(
            CompiledDeployment::class,
            $object->importVolume(
                'foo',
                '/bar',
                $promise
            )
        );
    }

    public function testImportVolumeBadType()
    {
        $volume = $this->createMock(VolumeInterface::class);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success')->with($volume);
        $promise->expects(self::once())->method('fail');

        $object = $this->buildObject();
        $object->addVolume('foo', $volume);

        self::assertInstanceOf(
            CompiledDeployment::class,
            $object->importVolume(
                'foo',
                '/bar',
                $promise
            )
        );
    }

    public function testAddServiceWrongService()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->addService('foo', new \stdClass());
    }

    public function testAddServiceWrongServiceName()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->addService(new \stdClass(), $this->createMock(Service::class));
    }

    public function testAddService()
    {
        self::assertInstanceOf(
            CompiledDeployment::class,
            $this->buildObject()->addService(
                'foo',
                $this->createMock(Service::class)
            )
        );
    }
    
    public function testAddSecretWrongSecret()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->addSecret('foo', new \stdClass());
    }

    public function testAddSecretWrongSecretName()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->addSecret(new \stdClass(), $this->createMock(Secret::class));
    }

    public function testAddSecret()
    {
        self::assertInstanceOf(
            CompiledDeployment::class,
            $this->buildObject()->addSecret(
                'foo',
                $this->createMock(Secret::class)
            )
        );
    }

    public function testAddIngressWrongIngress()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->addIngress('foo', new \stdClass());
    }

    public function testAddIngressWrongIngressName()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->addIngress(new \stdClass(), $this->createMock(Ingress::class));
    }

    public function testAddIngress()
    {
        self::assertInstanceOf(
            CompiledDeployment::class,
            $this->buildObject()->addIngress(
                'foo',
                $this->createMock(Ingress::class)
            )
        );
    }

    public function testAddPodWrongContainer()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->addPod('foo',  new \stdClass());
    }

    public function testAddPodWrongContainerName()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->addPod(new \stdClass(), $this->createMock(Pod::class));
    }

    public function testAddPodMissingBuildable()
    {
        $this->expectException(\DomainException::class);

        $this->buildObject()
            ->addPod(
                'foo',
                new Pod('foo', 1, [new Container('foo', 'bar', '1.2', [80], [], [])])
            );
    }

    public function testAddPod()
    {
        self::assertInstanceOf(
            CompiledDeployment::class,
            $this->buildObject()
                ->addBuildable(
                    (new Image('foo', 'bar', false, '1.2', ['foo' => 'bar']))
                )
                ->addPod(
                    'bar',
                    new Pod('bar', 1, [new Container('bar', 'foo', '1.2', [80], [], [])])
                )
                ->addPod(
                    'bar',
                    new Pod('bar', 1, [new Container('bar', 'registry/foo', '1.2', [80], [], [])])
                )
        );
    }

    public function testForeachHookBadCallback()
    {
        $this->expectException(\TypeError::class);

        $this->buildObject()->foreachHook(new \stdClass());
    }

    public function testForeachHook()
    {
        $cd = $this->buildObject();

        $cd->addHook(
            'foo1',
            $this->createMock(HookInterface::class)
        );

        $cd->addHook(
            'foo2',
            $this->createMock(HookInterface::class)
        );

        $count = 0;
        self::assertInstanceOf(
            CompiledDeployment::class,
            $cd->foreachHook(function ($hook) use (&$count) {
                self::assertInstanceOf(HookInterface::class, $hook);

                $count++;
            })
        );

        self::assertEquals(2, $count);
    }

    public function testForeachVolumeBadCallback()
    {
        $this->expectException(\TypeError::class);

        $this->buildObject()->foreachVolume(new \stdClass());
    }

    public function testForeachVolume()
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

        $count = 0;
        self::assertInstanceOf(
            CompiledDeployment::class,
            $cd->foreachVolume(function ($name, $volume) use (&$count) {
                self::assertEquals(0, \strpos($name, 'foo'));
                self::assertInstanceOf(Volume::class, $volume);

                $count++;
            })
        );

        self::assertEquals(2, $count);
    }

    public function testForeachBuildableBadCallback()
    {
        $this->expectException(\TypeError::class);

        $this->buildObject()->foreachBuildable(new \stdClass());
    }

    public function testForeachBuildable()
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
            new Pod('foo1', 1, [new Container('foo1', 'foo', '1.2', [80], [], [])])
        );

        $cd->addPod(
            'foo2',
            new Pod('foo2', 1, [new Container('foo2', 'foo', '1.2', [80], [], [])])
        );

        $cd->addPod(
            'bar1',
            new Pod('bar1', 1, [new Container('registry/bar1', 'bar', '1.2', [80], [], [])])
        );

        $count = 0;
        self::assertInstanceOf(
            CompiledDeployment::class,
            $cd->foreachBuildable(function ($buildable) use (&$count) {
                self::assertInstanceOf(BuildableInterface::class, $buildable);
                self::assertNotEquals('hello', $buildable->getName());

                $count++;
            })
        );

        self::assertEquals(2, $count);
    }

    public function testForeachPodBadCallback()
    {
        $this->expectException(\TypeError::class);

        $this->buildObject()->foreachPod(new \stdClass());
    }

    public function testForeachPod()
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
                        []
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
                        []
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
                        []
                    )
                ]
            )
        );

        $count = 0;
        self::assertInstanceOf(
            CompiledDeployment::class,
            $cd->foreachPod(function ($pod, $buildables, $volumes) use (&$count) {
                self::assertInstanceOf(Pod::class, $pod);
                self::assertNotEmpty($buildables);
                self::assertInstanceOf(
                    BuildableInterface::class,
                    \current(\current($buildables))
                );

                if ('bar1' === $pod->getName()) {
                    self::assertEmpty($volumes);
                } else {
                    self::assertNotEmpty(
                        $volumes
                    );

                    self::assertInstanceOf(
                        Volume::class,
                        \current($volumes)
                    );
                }

                $count++;
            })
        );

        self::assertEquals(3, $count);
    }

    public function testForeachServiceBadCallback()
    {
        $this->expectException(\TypeError::class);

        $this->buildObject()->foreachService(new \stdClass());
    }

    public function testForeachService()
    {
        $cd = $this->buildObject();

        $cd->addService(
            'foo1',
            $this->createMock(Service::class)
        );

        $cd->addService(
            'foo2',
            $this->createMock(Service::class)
        );

        $count = 0;
        self::assertInstanceOf(
            CompiledDeployment::class,
            $cd->foreachService(function ($service) use (&$count) {
                self::assertInstanceOf(Service::class, $service);

                $count++;
            })
        );

        self::assertEquals(2, $count);
    }

    public function testForeachSecretBadCallback()
    {
        $this->expectException(\TypeError::class);

        $this->buildObject()->foreachSecret(new \stdClass());
    }

    public function testForeachSecret()
    {
        $cd = $this->buildObject();

        $cd->addSecret(
            'foo1',
            $this->createMock(Secret::class)
        );

        $cd->addSecret(
            'foo2',
            $this->createMock(Secret::class)
        );

        $count = 0;
        self::assertInstanceOf(
            CompiledDeployment::class,
            $cd->foreachSecret(function ($secret) use (&$count) {
                self::assertInstanceOf(Secret::class, $secret);

                $count++;
            })
        );

        self::assertEquals(2, $count);
    }

    public function testForeachIngressBadCallback()
    {
        $this->expectException(\TypeError::class);

        $this->buildObject()->foreachIngress(new \stdClass());
    }

    public function testForeachIngress()
    {
        $cd = $this->buildObject();

        $cd->addIngress(
            'foo1',
            $this->createMock(Ingress::class)
        );

        $cd->addIngress(
            'foo2',
            $this->createMock(Ingress::class)
        );

        $count = 0;
        self::assertInstanceOf(
            CompiledDeployment::class,
            $cd->foreachIngress(function ($ingress) use (&$count) {
                self::assertInstanceOf(Ingress::class, $ingress);

                $count++;
            })
        );

        self::assertEquals(2, $count);
    }
}