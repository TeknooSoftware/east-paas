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
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\PersistentVolume;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(PersistentVolume::class)]
class PersistentVolumeTest extends TestCase
{
    private function buildObject(): PersistentVolume
    {
        return new PersistentVolume('foo', 'bar', 'foobar', 'barfoo', false, true);
    }

    public function testGetName(): void
    {
        $this->assertEquals('foo', $this->buildObject()->getName());
    }

    public function testGetMountPath(): void
    {
        $this->assertEquals('bar', $this->buildObject()->getMountPath());
    }

    public function testGetStorageIdentifier(): void
    {
        $this->assertEquals('foobar', $this->buildObject()->getStorageIdentifier());
    }

    public function testGetStorageSize(): void
    {
        $this->assertEquals('barfoo', $this->buildObject()->getStorageSize());
    }

    public function testIsResetOnDeployment(): void
    {
        $this->assertFalse($this->buildObject()->isResetOnDeployment());

        $this->assertTrue(new PersistentVolume('foo', 'bar', 'foobar', 'barfoo', true)->isResetOnDeployment());
    }

    public function testAllowedForWriteMany(): void
    {
        $this->assertTrue($this->buildObject()->allowedForWriteMany());

        $this->assertFalse(new PersistentVolume('foo', 'bar', 'foobar', 'barfoo', true, false)->allowedForWriteMany());
    }
}
