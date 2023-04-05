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

namespace Teknoo\Tests\East\Paas\Compilation\CompiledDeployment\Image;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\EmbeddedVolumeImage;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Compilation\CompiledDeployment\Image\EmbeddedVolumeImage
 */
class EmbeddedVolumeImageTest extends TestCase
{
    private function buildObject(): EmbeddedVolumeImage
    {
        return new EmbeddedVolumeImage(
            'foo',
            '1.2',
            'orignal',
            [
                new Volume('foo', ['foo', 'bar'], 'bar', '/mount')
            ]
        );
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
        $image1 = $this->buildObject();
        self::assertEquals('foo', $image1->getUrl());
        $image2 = $image1->withRegistry('bar');
        self::assertInstanceOf(EmbeddedVolumeImage::class, $image2);
        self::assertNotSame($image1, $image2);
        self::assertEquals('foo', $image1->getUrl());
        self::assertEquals('bar/foo', $image2->getUrl());
    }

    public function testGetPath()
    {
        self::assertEquals(
            '',
            $this->buildObject()->getPath()
        );
    }

    public function testGetTag()
    {
        self::assertEquals(
            '1.2',
            $this->buildObject()->getTag()
        );
    }

    public function testGetOriginalName()
    {
        self::assertEquals(
            'orignal',
            $this->buildObject()->getOriginalName()
        );
    }

    public function testGetVariables()
    {
        self::assertEquals(
            [],
            $this->buildObject()->getVariables()
        );
    }

    public function testGetVolumes()
    {
        self::assertEquals(
            [
                new Volume('foo', ['foo', 'bar'], 'bar', '/mount')
            ],
            $this->buildObject()->getVolumes()
        );
    }
}