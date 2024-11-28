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

namespace Teknoo\Tests\East\Paas\Compilation\Value;

use DomainException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\Reference;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(DefaultsBag::class)]
class DefaultsBagTest extends TestCase
{
    private function buildObject(): DefaultsBag
    {
        return new DefaultsBag();
    }

    public function testSet()
    {
        self::assertInstanceOf(
            DefaultsBag::class,
            $this->buildObject()->set('foo', 'bar'),
        );

        self::assertInstanceOf(
            DefaultsBag::class,
            $this->buildObject()->set('foo', null),
        );
    }

    public function testForCluster()
    {
        $parent = $this->buildObject();
        $parent->set('foo', 'bar');
        $child = $parent->forCluster('foo');

        self::assertInstanceOf(
            DefaultsBag::class,
            $child
        );

        self::assertNotSame(
            $child,
            $parent,
        );
    }

    public function testGetBagFor()
    {
        $parent = $this->buildObject();
        $parent->set('foo', 'bar');
        $child = $parent->forCluster('foo');

        self::assertInstanceOf(
            DefaultsBag::class,
            $parent->getBagFor('foo'),
        );

        self::assertSame(
            $child,
            $parent->getBagFor('foo'),
        );

        self::assertSame(
            $parent,
            $parent->getBagFor('bar'),
        );
    }

    public function testGetReferenceOfNotDefined()
    {
        $this->expectException(DomainException::class);
        $this->buildObject()->getReference('foo');
    }

    public function testGetReferenceOfNotDefinedFromChild()
    {
        $this->expectException(DomainException::class);
        $this->buildObject()
            ->forCluster('bar')
            ->getReference('foo');
    }

    public function testGetReferenceOfNull()
    {
        self::assertInstanceOf(
            Reference::class,
            $this->buildObject()
                ->set('foo', null)
                ->getReference('foo')
        );
    }

    public function testGetReference()
    {
        self::assertInstanceOf(
            Reference::class,
            $this->buildObject()
                ->set('foo', 'bar')
                ->getReference('foo')
        );
    }

    public function testGetReferenceFromParent()
    {
        self::assertInstanceOf(
            Reference::class,
            $this->buildObject()
                ->set('foo', 'bar')
                ->forCluster('subCluster')
                ->getReference('foo')
        );
    }

    public function testGetReferenceFromChild()
    {
        self::assertInstanceOf(
            Reference::class,
            $this->buildObject()
                ->forCluster('subCluster')
                ->set('foo', 'bar')
                ->getReference('foo')
        );
    }

    public function testResolveOfNotDefined()
    {
        $this->expectException(DomainException::class);
        $this->buildObject()->resolve(new Reference('foo'));
    }

    public function testResolveOfNotDefinedFromChild()
    {
        $this->expectException(DomainException::class);
        $this->buildObject()
            ->forCluster('bar')
            ->resolve(new Reference('foo'));
    }

    public function testResolveOfNull()
    {
        self::assertNull(
            $this->buildObject()
                ->set('foo', null)
                ->resolve(new Reference('foo'))
        );
    }

    public function testResolve()
    {
        self::assertEquals(
            'bar',
            $this->buildObject()
                ->set('foo', 'bar')
                ->resolve(new Reference('foo'))
        );
    }

    public function testResolveFromParent()
    {
        self::assertEquals(
            'bar',
            $this->buildObject()
                ->set('foo', 'bar')
                ->forCluster('subCluster')
                ->resolve(new Reference('foo'))
        );
    }

    public function testResolveFromChild()
    {
        self::assertEquals(
            'bar',
            $this->buildObject()
                ->forCluster('subCluster')
                ->set('foo', 'bar')
                ->resolve(new Reference('foo'))
        );

        self::assertEquals(
            'bar',
            $this->buildObject()
                ->set('foo', 'foo')
                ->forCluster('subCluster')
                ->set('foo', 'bar')
                ->resolve(new Reference('foo'))
        );
    }
}
