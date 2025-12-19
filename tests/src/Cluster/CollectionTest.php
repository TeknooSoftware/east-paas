<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Cluster;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Cluster\Collection;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Collection::class)]
class CollectionTest extends TestCase
{
    public function testGetIterator(): void
    {
        $collection = new Collection([
            $this->createStub(DriverInterface::class),
            $this->createStub(DriverInterface::class),
        ]);

        $count = 0;
        foreach ($collection as $client) {
            $this->assertInstanceOf(DriverInterface::class, $client);
            ++$count;
        }

        $this->assertEquals(2, $count);
    }
}
