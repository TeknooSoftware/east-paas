<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Container;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Container\Image;

/**
 * @covers \Teknoo\East\Paas\Container\Image
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

    public function testUpdateUrl()
    {
        $image1 = $this->buildObject();
        self::assertEquals('foo', $image1->getUrl());
        $image2 = $image1->updateUrl('bar');
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