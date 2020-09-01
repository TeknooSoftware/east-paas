<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Messenger\Message;

use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Parameter;
use PHPUnit\Framework\TestCase;
use Teknoo\Immutable\Exception\ImmutableException;

/**
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
