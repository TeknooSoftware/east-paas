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

namespace Teknoo\Tests\East\Paas\Compilation\CompiledDeployment\Volume;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Volume::class)]
class VolumeTest extends TestCase
{
    private function buildObject(): Volume
    {
        return new Volume('foo', ['foo', 'bar'], 'bar', '/mount', ['bar']);
    }

    public function testGetName(): void
    {
        $this->assertEquals('foo', $this->buildObject()->getName());
    }

    public function testWithRegistryAndGetUrl(): void
    {
        $volume1 = $this->buildObject();
        $this->assertEquals('foo', $volume1->getUrl());
        $volume2 = $volume1->withRegistry('bar');
        $this->assertInstanceOf(Volume::class, $volume2);
        $this->assertNotSame($volume1, $volume2);
        $this->assertEquals('foo', $volume1->getUrl());
        $this->assertEquals('bar/foo', $volume2->getUrl());
    }

    public function testGetPath(): void
    {
        $this->assertEquals(['foo', 'bar'], $this->buildObject()->getPaths());
    }

    public function testGetLocalPath(): void
    {
        $this->assertEquals('bar', $this->buildObject()->getLocalPath());
    }

    public function testGetMountPath(): void
    {
        $this->assertEquals('/mount', $this->buildObject()->getMountPath());

        $this->assertEquals('mount', new Volume('foo', ['foo', 'bar'], 'bar', 'mount')->getMountPath());
    }


    public function testGetWritable(): void
    {
        $this->assertEquals(['bar'], $this->buildObject()->getWritables());
    }

    public function testIsEmbedded(): void
    {
        $this->assertFalse($this->buildObject()->isEmbedded());

        $this->assertTrue(new Volume('foo', ['foo', 'bar'], 'bar', 'mount', [], true)->isEmbedded());
    }

    public function testImport(): void
    {
        $volume = $this->buildObject();

        $volumeImported = $volume->import('foo');

        $this->assertInstanceOf(Volume::class, $volumeImported);

        $this->assertNotEquals($volume, $volumeImported);

        $this->assertEquals('/mount', $volume->getMountPath());

        $this->assertEquals('foo', $volumeImported->getMountPath());
    }
}
