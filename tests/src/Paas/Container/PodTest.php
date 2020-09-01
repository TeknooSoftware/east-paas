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