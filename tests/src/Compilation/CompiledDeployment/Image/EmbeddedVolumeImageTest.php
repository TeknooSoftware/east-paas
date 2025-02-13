<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
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
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Compilation\CompiledDeployment\Image;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\EmbeddedVolumeImage;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(EmbeddedVolumeImage::class)]
class EmbeddedVolumeImageTest extends TestCase
{
    private function buildObject(): EmbeddedVolumeImage
    {
        return new EmbeddedVolumeImage(
            'foo',
            '1.2',
            'original',
            'originalTag',
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
            'original',
            $this->buildObject()->getOriginalName()
        );
    }

    public function testGetOriginalTag()
    {
        self::assertEquals(
            'originalTag',
            $this->buildObject()->getOriginalTag()
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