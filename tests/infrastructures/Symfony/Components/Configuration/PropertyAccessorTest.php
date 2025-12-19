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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\SerializingConfiguration;

use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use Teknoo\East\Paas\Infrastructures\Symfony\Configuration\PropertyAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor as SymfonyPropertyAccessor;
use TypeError;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(PropertyAccessor::class)]
class PropertyAccessorTest extends TestCase
{
    private (SymfonyPropertyAccessor&MockObject)|(SymfonyPropertyAccessor&Stub)|null $propertyAccessor = null;

    private function getPropertyAccessor(bool $stub = false): (SymfonyPropertyAccessor&Stub)|(SymfonyPropertyAccessor&MockObject)
    {
        if (!$this->propertyAccessor instanceof SymfonyPropertyAccessor) {
            if ($stub) {
                $this->propertyAccessor = $this->createStub(SymfonyPropertyAccessor::class);
            } else {
                $this->propertyAccessor = $this->createMock(SymfonyPropertyAccessor::class);
            }
        }

        return $this->propertyAccessor;
    }

    public function buildAccessor(): PropertyAccessor
    {
        return new PropertyAccessor(
            $this->getPropertyAccessor(true)
        );
    }

    public function testSetValueBadArray(): void
    {
        $this->expectException(TypeError::class);
        $a = new stdClass();
        $this->buildAccessor()->setValue($a, 'foo', 'bar');
    }

    public function testSetValueBadPath(): void
    {
        $this->expectException(TypeError::class);
        $a = ['foo'];
        $this->buildAccessor()->setValue($a, new stdClass(), 'bar');
    }

    public function testSetValue(): void
    {
        $this->getPropertyAccessor()
            ->expects($this->once())
            ->method('setValue')
            ->with($array = ['foo' => 'bar'], $path = 'foo.bar', $value = 'foo');

        $this->assertInstanceOf(PropertyAccessor::class, $this->buildAccessor()->setValue($array, $path, $value));
    }

    public function testGetValueBadArray(): void
    {
        $this->expectException(TypeError::class);
        $this->buildAccessor()->getValue(new stdClass(), 'foo', function (): void {});
    }

    public function testGetValueBadPath(): void
    {
        $this->expectException(TypeError::class);
        $this->buildAccessor()->getValue(['foo'], new stdClass(), function (): void {});
    }

    public function testGetValueBadCallback(): void
    {
        $this->expectException(TypeError::class);
        $this->buildAccessor()->getValue(['foo'], 'bar', new stdClass());
    }

    public function testGetValueFoundWithoutDefault(): void
    {
        $array = ['foo' => ['bar' => 'foo']];
        $path = 'foo.bar';
        $called = false;
        $callback = function ($value) use (&$called): void {
            $this->assertEquals('foo', $value);
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

        $this->assertInstanceOf(PropertyAccessor::class, $this->buildAccessor()->getValue($array, $path, $callback));

        $this->assertTrue($called);
    }

    public function testGetValueFoundWithDefault(): void
    {
        $array = ['foo' => ['bar' => 'foo']];
        $path = 'foo.bar';
        $called = false;
        $callback = function ($value) use (&$called): void {
            $this->assertEquals('foo', $value);
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

        $this->assertInstanceOf(PropertyAccessor::class, $this->buildAccessor()->getValue($array, $path, $callback, 'bar'));

        $this->assertTrue($called);
    }

    public function testGetValueNotFoundWithoutDefault(): void
    {
        $array = ['foo' => ['bar' => 'foo']];
        $path = 'foo.bar';
        $callback = function ($value) use (&$called): void {
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

        $this->assertInstanceOf(PropertyAccessor::class, $this->buildAccessor()->getValue($array, $path, $callback));
    }

    public function testGetValueNotFoundWithDefault(): void
    {
        $array = ['foo' => ['bar' => 'foo']];
        $path = 'foo.bar';
        $called = false;
        $callback = function ($value) use (&$called): void {
            $this->assertEquals('bar', $value);
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

        $this->assertInstanceOf(PropertyAccessor::class, $this->buildAccessor()->getValue($array, $path, $callback, 'bar'));

        $this->assertTrue($called);
    }
}
