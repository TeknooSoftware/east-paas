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
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Compilation\CompiledDeployment\Volume;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\PersistentVolume;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\PersistentVolume
 */
class PersistentVolumeTest extends TestCase
{
    private function buildObject(): PersistentVolume
    {
        return new PersistentVolume('foo', 'bar', 'foobar', 'barfoo', false);
    }

    public function testGetName()
    {
        self::assertEquals(
            'foo',
            $this->buildObject()->getName()
        );
    }

    public function testGetMountPath()
    {
        self::assertEquals(
            'bar',
            $this->buildObject()->getMountPath()
        );
    }

    public function testGetStorageIdentifier()
    {
        self::assertEquals(
            'foobar',
            $this->buildObject()->getStorageIdentifier()
        );
    }

    public function testGetStorageSize()
    {
        self::assertEquals(
            'barfoo',
            $this->buildObject()->getStorageSize()
        );
    }

    public function testIsResetOnDeployment()
    {
        self::assertFalse(
            $this->buildObject()->isResetOnDeployment()
        );

        self::assertTrue(
            (new PersistentVolume('foo', 'bar', 'foobar', 'barfoo', true))->isResetOnDeployment()
        );
    }
}