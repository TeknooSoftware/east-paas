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

namespace Teknoo\Tests\East\Paas\Container\Image;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Container\Image\Image;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Container\Image\Image
 */
class ImageTest extends TestCase
{
    private function buildObject(): Image
    {
        return new Image('foo', 'bar', true, '1.2', ['foo' => 'bar']);
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
        self::assertInstanceOf(Image::class, $image2);
        self::assertNotSame($image1, $image2);
        self::assertEquals('foo', $image1->getUrl());
        self::assertEquals('bar/foo', $image2->getUrl());
    }

    public function testGetPath()
    {
        self::assertEquals(
            'bar',
            $this->buildObject()->getPath()
        );
    }

    public function testIsLibrary()
    {
        self::assertTrue(
            $this->buildObject()->isLibrary()
        );
    }

    public function testGetTag()
    {
        self::assertEquals(
            '1.2',
            $this->buildObject()->getTag()
        );
    }

    public function testGetVariables()
    {
        self::assertEquals(
            ['foo' => 'bar'],
            $this->buildObject()->getVariables()
        );
    }
}