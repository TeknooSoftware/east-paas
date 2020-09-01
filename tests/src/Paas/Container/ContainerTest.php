<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Container;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Container\Container;

/**
 * @covers \Teknoo\East\Paas\Container\Container
 */
class ContainerTest extends TestCase
{
    private function buildObject(): Container
    {
        return new Container('foo', 'bar', '1.2', [80], ['foo', 'bar'], ['bar' => 'foo']);
    }

    public function testGetName()
    {
        self::assertEquals(
            'foo',
            $this->buildObject()->getName()
        );
    }

    public function testGetImage()
    {
        self::assertEquals(
            'bar',
            $this->buildObject()->getImage()
        );
    }

    public function testGetVersion()
    {
        self::assertEquals(
            '1.2',
            $this->buildObject()->getVersion()
        );
    }

    public function testGetListen()
    {
        self::assertEquals(
            [80],
            $this->buildObject()->getListen()
        );
    }

    public function testGetVolumes()
    {
        self::assertEquals(
            ['foo', 'bar'],
            $this->buildObject()->getVolumes()
        );
    }

    public function testGetVariables()
    {
        self::assertEquals(
            ['bar' => 'foo'],
            $this->buildObject()->getVariables()
        );
    }
}