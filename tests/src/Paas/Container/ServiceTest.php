<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Container;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Container\Service;

/**
 * @covers \Teknoo\East\Paas\Container\Service
 */
class ServiceTest extends TestCase
{
    private function buildObject(): Service
    {
        return new Service('foo', [80 => 8080], 'TCP');
    }

    public function testGetName()
    {
        self::assertEquals(
            'foo',
            $this->buildObject()->getName()
        );
    }

    public function testGetPorts()
    {
        self::assertEquals(
            [80 => 8080],
            $this->buildObject()->getPorts()
        );
    }

    public function testGetProtocol()
    {
        self::assertEquals(
            'TCP',
            $this->buildObject()->getProtocol()
        );
    }
}