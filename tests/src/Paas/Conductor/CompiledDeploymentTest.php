<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Conductor;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Container\Image;
use Teknoo\East\Paas\Container\Container;
use Teknoo\East\Paas\Container\Pod;
use Teknoo\East\Paas\Container\Service;
use Teknoo\East\Paas\Container\Volume;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;

/**
 * @covers \Teknoo\East\Paas\Conductor\CompiledDeployment
 */
class CompiledDeploymentTest extends TestCase
{
    private function buildObject(): CompiledDeployment
    {
        return new CompiledDeployment();
    }

    public function testAddImageWrongImage()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->addImage(new \stdClass());
    }

    public function testAddImage()
    {
        self::assertInstanceOf(
            CompiledDeployment::class,
            $this->buildObject()->addImage(
                $this->createMock(Image::class)
            )
        );
    }

    public function testDefineHookWrongHook()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->defineHook('foo', new \stdClass());
    }

    public function testDefineHookWrongHookName()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->defineHook(new \stdClass(), $this->createMock(HookInterface::class));
    }

    public function testDefineHook()
    {
        self::assertInstanceOf(
            CompiledDeployment::class,
            $this->buildObject()->defineHook(
                'foo',
                $this->createMock(HookInterface::class)
            )
        );
    }

    public function testDefineVolumeWrongVolume()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->defineVolume('foo', new \stdClass());
    }

    public function testDefineVolumeWrongVolumeName()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->defineVolume(new \stdClass(), $this->createMock(Volume::class));
    }

    public function testDefineVolume()
    {
        self::assertInstanceOf(
            CompiledDeployment::class,
            $this->buildObject()->defineVolume(
                'foo',
                $this->createMock(Volume::class)
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

    public function testAddPodMissingImage()
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
                ->addImage(
                    (new Image('foo', 'bar', false, '1.2', ['foo' => 'bar']))
                )
                ->addPod(
                    'bar',
                    new Pod('bar', 1, [new Container('bar', 'foo', '1.2', [80], [], [])])
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

        $cd->defineHook(
            'foo1',
            $this->createMock(HookInterface::class)
        );

        $cd->defineHook(
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

        $cd->defineVolume(
            'foo1',
            new Volume('foo1', 'bar', [])
        );

        $cd->defineVolume(
            'foo2',
            new Volume('foo2', 'bar', [])
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

    public function testForeachImageBadCallback()
    {
        $this->expectException(\TypeError::class);

        $this->buildObject()->foreachImage(new \stdClass());
    }

    public function testForeachImage()
    {
        $cd = $this->buildObject();

        $cd->addImage(
            (new Image('foo', 'bar', false, '1.2', ['foo' => 'bar']))
        );

        $cd->addImage(
            (new Image('bar', 'foo', false, '1.2', ['foo' => 'bar']))
        );

        $cd->addImage(
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
            new Pod('bar1', 1, [new Container('bar1', 'bar', '1.2', [80], [], [])])
        );

        $count = 0;
        self::assertInstanceOf(
            CompiledDeployment::class,
            $cd->foreachImage(function ($image) use (&$count) {
                self::assertInstanceOf(Image::class, $image);
                self::assertNotEquals('hello', $image->getName());

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

        $cd->addImage(
            (new Image('foo', 'bar', false, '1.2', ['foo' => 'bar']))
        );

        $cd->addImage(
            (new Image('bar', 'foo', false, '1.2', ['foo' => 'bar']))
        );

        $cd->addImage(
            (new Image('hello', 'world', false, '1.2', ['foo' => 'bar']))
        );

        $cd->defineVolume(
            'foo',
            (new Volume('foo1', '/foo', []))
        );

        $cd->defineVolume(
            'bar',
            (new Volume('bar1', '/bar', []))
        );

        $cd->addPod(
            'foo1',
            new Pod('foo1', 1, [new Container('foo1', 'foo', '1.2', [80], ['foo'], [])])
        );

        $cd->addPod(
            'foo2',
            new Pod('foo2', 1, [new Container('foo2', 'foo', '1.2', [80], ['bar'], [])])
        );

        $cd->addPod(
            'bar1',
            new Pod('bar1', 1, [new Container('bar1', 'bar', '1.2', [80], [], [])])
        );

        $count = 0;
        self::assertInstanceOf(
            CompiledDeployment::class,
            $cd->foreachPod(function ($pod, $images, $volumes) use (&$count) {
                self::assertInstanceOf(Pod::class, $pod);
                self::assertNotEmpty($images);
                self::assertInstanceOf(
                    Image::class,
                    \current(\current($images))
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
}