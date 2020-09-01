<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Container;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Container\Volume;

/**
 * @covers \Teknoo\East\Paas\Container\Volume
 */
class VolumeTest extends TestCase
{
    private function buildObject(): Volume
    {
        return new Volume('foo', 'bar', ['foo', 'bar']);
    }

    public function testGetName()
    {
        self::assertEquals(
            'foo',
            $this->buildObject()->getName()
        );
    }

    public function testUpdateUrl()
    {
        $volume1 = $this->buildObject();
        self::assertEquals('foo', $volume1->getUrl());
        $volume2 = $volume1->updateUrl('bar');
        self::assertInstanceOf(Volume::class, $volume2);
        self::assertNotSame($volume1, $volume2);
        self::assertEquals('foo', $volume1->getUrl());
        self::assertEquals('bar/foo', $volume2->getUrl());
    }

    public function testGetAndUpdateMountPath()
    {
        $volume1 = $this->buildObject();
        self::assertEquals('bar', $volume1->getMountPath());
        $volume2 = $volume1->updateMountPath('foo');
        self::assertInstanceOf(Volume::class, $volume2);
        self::assertNotSame($volume1, $volume2);
        self::assertEquals('bar', $volume1->getMountPath());
        self::assertEquals('foo', $volume2->getMountPath());
    }

    public function testGetTarget()
    {
        self::assertEquals(
            'bar',
            $this->buildObject()->getTarget()
        );
    }

    public function testGetPath()
    {
        self::assertEquals(
            ['foo', 'bar'],
            $this->buildObject()->getPaths()
        );
    }
}