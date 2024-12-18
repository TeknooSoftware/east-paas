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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\SerializingConfiguration;

use PHPUnit\Framework\Attributes\CoversClass;
use Teknoo\East\Paas\Infrastructures\Symfony\Configuration\PropertyAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor as SymfonyPropertyAccessor;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(PropertyAccessor::class)]
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
        $a = new \stdClass();
        $this->buildAccessor()->setValue($a, 'foo', 'bar');
    }

    public function testSetValueBadPath()
    {
        $this->expectException(\TypeError::class);
        $a = ['foo'];
        $this->buildAccessor()->setValue($a, new \stdClass(), 'bar');
    }

    public function testSetValue()
    {
        $this->getPropertyAccessor()
            ->expects($this->once())
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
            ->expects($this->once())
            ->method('isReadable')
            ->with($array, $path)
            ->willReturn(true);

        $this->getPropertyAccessor()
            ->expects($this->once())
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
            ->expects($this->once())
            ->method('isReadable')
            ->with($array, $path)
            ->willReturn(true);

        $this->getPropertyAccessor()
            ->expects($this->once())
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
            ->expects($this->once())
            ->method('isReadable')
            ->with($array, $path)
            ->willReturn(false);

        $this->getPropertyAccessor()
            ->expects($this->never())
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
            ->expects($this->once())
            ->method('isReadable')
            ->with($array, $path)
            ->willReturn(false);

        $this->getPropertyAccessor()
            ->expects($this->never())
            ->method('getValue');

        self::assertInstanceOf(
            PropertyAccessor::class,
            $this->buildAccessor()->getValue($array, $path, $callback, 'bar')
        );

        self::assertTrue($called);
    }
}
