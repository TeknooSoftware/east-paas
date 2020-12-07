<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
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

namespace Teknoo\Tests\East\Paas\Container;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Container\Volume;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Container\Volume
 */
class VolumeTest extends TestCase
{
    private function buildObject(): Volume
    {
        return new Volume('foo', ['foo', 'bar'], 'bar');
    }

    public function testGetName()
    {
        self::assertEquals(
            'foo',
            $this->buildObject()->getName()
        );
    }

    public function testWithRegistryAndGetUrl()
    {
        $volume1 = $this->buildObject();
        self::assertEquals('foo', $volume1->getUrl());
        $volume2 = $volume1->withRegistry('bar');
        self::assertInstanceOf(Volume::class, $volume2);
        self::assertNotSame($volume1, $volume2);
        self::assertEquals('foo', $volume1->getUrl());
        self::assertEquals('bar/foo', $volume2->getUrl());
    }

    public function testGetPath()
    {
        self::assertEquals(
            ['foo', 'bar'],
            $this->buildObject()->getPaths()
        );
    }

    public function testGetLocalPath()
    {
        self::assertEquals(
            'bar',
            $this->buildObject()->getLocalPath()
        );
    }

    public function testGetMountPath()
    {
        self::assertEquals(
            '',
            $this->buildObject()->getMountPath()
        );

        self::assertEquals(
            'mount',
            (new Volume('foo', ['foo', 'bar'], 'bar', 'mount'))->getMountPath()
        );
    }

    public function testIsEmbedded()
    {
        self::assertFalse(
            $this->buildObject()->isEmbedded()
        );

        self::assertTrue(
            (new Volume('foo', ['foo', 'bar'], 'bar', 'mount', true))->isEmbedded()
        );
    }

    public function testImport()
    {
        $volume = $this->buildObject();

        $volumeImported = $volume->import('foo');

        self::assertInstanceOf(
            Volume::class,
            $volumeImported
        );

        self::assertNotEquals(
            $volume,
            $volumeImported
        );

        self::assertEquals(
            '',
            $volume->getMountPath()
        );

        self::assertEquals(
            'foo',
            $volumeImported->getMountPath()
        );
    }
}