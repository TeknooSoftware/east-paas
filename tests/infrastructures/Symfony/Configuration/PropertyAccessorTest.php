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
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\SerializingConfiguration;

use Teknoo\East\Paas\Infrastructures\Symfony\Configuration\PropertyAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor as SymfonyPropertyAccessor;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Symfony\Configuration\PropertyAccessor
 */
class PropertyAccessorTest extends TestCase
{
    private ?SymfonyPropertyAccessor $propertyAccessor = null;

    /**
     * @return SymfonyPropertyAccessor|MockObject
     */
    private function getPropertyAccessor(): SymfonyPropertyAccessor
    {
        if (!$this->propertyAccessor instanceof SymfonyPropertyAccessor) {
            $this->propertyAccessor = $this->createMock(SymfonyPropertyAccessor::class);
        }

        return $this->propertyAccessor;
    }

    public function buildAccessor(): PropertyAccessor
    {
        return new PropertyAccessor(
            $this->getPropertyAccessor()
        );
    }

    public function testSetValueBadArray()
    {
        $this->expectException(\TypeError::class);
        $this->buildAccessor()->setValue(new \stdClass(), 'foo', 'bar');
    }

    public function testSetValueBadPath()
    {
        $this->expectException(\TypeError::class);
        $this->buildAccessor()->setValue(['foo'], new \stdClass(), 'bar');
    }

    public function testSetValue()
    {
        $this->getPropertyAccessor()
            ->expects(self::once())
            ->method('setValue')
            ->with($array = ['foo' => 'bar'], $path = 'foo.bar', $value = 'foo');

        self::assertInstanceOf(
            PropertyAccessor::class,
            $this->buildAccessor()->setValue($array, $path, $value)
        );
    }

    public function testGetValueBadArray()
    {
        $this->expectException(\TypeError::class);
        $this->buildAccessor()->getValue(new \stdClass(), 'foo', function() {});
    }

    public function testGetValueBadPath()
    {
        $this->expectException(\TypeError::class);
        $this->buildAccessor()->getValue(['foo'], new \stdClass(), function() {});
    }

    public function testGetValueBadCallback()
    {
        $this->expectException(\TypeError::class);
        $this->buildAccessor()->getValue(['foo'], 'bar', new \stdClass());
    }

    public function testGetValueFoundWithoutDefault()
    {
        $array = ['foo' => ['bar' => 'foo']];
        $path = 'foo.bar';
        $called = false;
        $callback = function ($value) use (&$called) {
            self::assertEquals('foo', $value);
            $called = true;
        };

        $this->getPropertyAccessor()
            ->expects(self::once())
            ->method('isReadable')
            ->with($array, $path)
            ->willReturn(true);

        $this->getPropertyAccessor()
            ->expects(self::once())
            ->method('getValue')
            ->with($array, $path)
            ->willReturn('foo');

        self::assertInstanceOf(
            PropertyAccessor::class,
            $this->buildAccessor()->getValue($array, $path, $callback)
        );

        self::assertTrue($called);
    }

    public function testGetValueFoundWithDefault()
    {
        $array = ['foo' => ['bar' => 'foo']];
        $path = 'foo.bar';
        $called = false;
        $callback = function ($value) use (&$called) {
            self::assertEquals('foo', $value);
            $called = true;
        };

        $this->getPropertyAccessor()
            ->expects(self::once())
            ->method('isReadable')
            ->with($array, $path)
            ->willReturn(true);

        $this->getPropertyAccessor()
            ->expects(self::once())
            ->method('getValue')
            ->with($array, $path)
            ->willReturn('foo');

        self::assertInstanceOf(
            PropertyAccessor::class,
            $this->buildAccessor()->getValue($array, $path, $callback, 'bar')
        );

        self::assertTrue($called);
    }

    public function testGetValueNotFoundWithoutDefault()
    {
        $array = ['foo' => ['bar' => 'foo']];
        $path = 'foo.bar';
        $callback = function ($value) use (&$called) {
            self::fail('must be not called');
        };

        $this->getPropertyAccessor()
            ->expects(self::once())
            ->method('isReadable')
            ->with($array, $path)
            ->willReturn(false);

        $this->getPropertyAccessor()
            ->expects(self::never())
            ->method('getValue');

        self::assertInstanceOf(
            PropertyAccessor::class,
            $this->buildAccessor()->getValue($array, $path, $callback)
        );
    }

    public function testGetValueNotFoundWithDefault()
    {
        $array = ['foo' => ['bar' => 'foo']];
        $path = 'foo.bar';
        $called = false;
        $callback = function ($value) use (&$called) {
            self::assertEquals('bar', $value);
            $called = true;
        };

        $this->getPropertyAccessor()
            ->expects(self::once())
            ->method('isReadable')
            ->with($array, $path)
            ->willReturn(false);

        $this->getPropertyAccessor()
            ->expects(self::never())
            ->method('getValue');

        self::assertInstanceOf(
            PropertyAccessor::class,
            $this->buildAccessor()->getValue($array, $path, $callback, 'bar')
        );

        self::assertTrue($called);
    }
}
