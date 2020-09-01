<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Container;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Container\Container;
use Teknoo\East\Paas\Container\Pod;

/**
 * @covers \Teknoo\East\Paas\Container\Pod
 */
class PodTest extends TestCase
{
    private function buildObject(): Pod
    {
        return new Pod('foo', 2, [$this->createMock(Container::class)]);
    }

    public function testGetName()
    {
        self::assertEquals(
            'foo',
            $this->buildObject()->getName()
        );
    }

    public function testGetReplicas()
    {
        self::assertEquals(
            '2',
            $this->buildObject()->getReplicas()
        );
    }

    public function testGetIterator()
    {
        foreach ($this->buildObject() as $container) {
            self::assertInstanceOf(Container::class, $container);
        }
    }
}