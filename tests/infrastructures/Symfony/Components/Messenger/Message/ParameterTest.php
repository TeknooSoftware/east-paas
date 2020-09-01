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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Messenger\Message;

use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Parameter;
use PHPUnit\Framework\TestCase;
use Teknoo\Immutable\Exception\ImmutableException;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Parameter
 */
class ParameterTest extends TestCase
{
    public function buildParameter()
    {
        return new Parameter('foo', 'bar');
    }

    public function testContructorUnique()
    {
        $this->expectException(ImmutableException::class);
        $this->buildParameter()->__construct('foo', 'bar');
    }

    public function testGetName()
    {
        self::assertEquals(
            '%foo',
            $this->buildParameter()->getName()
        );
    }

    public function testGetValue()
    {
        self::assertEquals(
            'bar',
            $this->buildParameter()->getValue()
        );
    }
}