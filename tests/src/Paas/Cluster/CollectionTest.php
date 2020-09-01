<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Cluster;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Cluster\Collection;
use Teknoo\East\Paas\Contracts\Cluster\ClientInterface;

/**
 * @covers \Teknoo\East\Paas\Cluster\Collection
 */
class CollectionTest extends TestCase
{
    public function testGetIterator()
    {
        $collection = new Collection([
            $this->createMock(ClientInterface::class),
            $this->createMock(ClientInterface::class),
        ]);

        $count = 0;
        foreach ($collection as $client) {
            self::assertInstanceOf(ClientInterface::class, $client);
            $count++;
        }

        self::assertEquals(2, $count);
    }
}