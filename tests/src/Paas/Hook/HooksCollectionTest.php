<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Hook;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\East\Paas\Hook\HooksCollection;

/**
 * @covers \Teknoo\East\Paas\Hook\HooksCollection
 */
class HooksCollectionTest extends TestCase
{
    public function testGetIterator()
    {
        $collection = new HooksCollection([
            $this->createMock(HookInterface::class),
            $this->createMock(HookInterface::class),
        ]);

        $count = 0;
        foreach ($collection as $hook) {
            self::assertInstanceOf(HookInterface::class, $hook);
            $count++;
        }

        self::assertEquals(2, $count);
    }
}