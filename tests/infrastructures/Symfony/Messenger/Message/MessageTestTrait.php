<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Messenger\Message;

use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\MessageTrait;
use Teknoo\Immutable\Exception\ImmutableException;

trait MessageTestTrait
{
    /**
     * @return MessageTrait
     */
    abstract public function buildMessage();

    public function testContructorUnique()
    {
        $this->expectException(ImmutableException::class);
        $this->buildMessage()->__construct('foo');
    }

    public function testGetMessage()
    {
        self::assertEquals(
            'fooBar',
            $this->buildMessage()->getMessage()
        );
    }

    public function testToString()
    {
        self::assertEquals(
            'fooBar',
            (string) $this->buildMessage()
        );
    }
}
