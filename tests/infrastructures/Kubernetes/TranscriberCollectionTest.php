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

namespace Teknoo\Tests\East\Paas\Infrastructures\Kubernetes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\TranscriberCollection;

use function iterator_to_array;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(TranscriberCollection::class)]
class TranscriberCollectionTest extends TestCase
{
    public function testAdd(): void
    {
        $this->assertInstanceOf(TranscriberCollection::class, new TranscriberCollection()->add(
            1,
            $this->createStub(TranscriberInterface::class)
        ));
    }

    public function testGetIterator(): void
    {
        $collection = new TranscriberCollection();

        $c1 = $this->createStub(TranscriberInterface::class);
        $c2 = $this->createStub(TranscriberInterface::class);
        $c3 = $this->createStub(TranscriberInterface::class);

        $collection->add(2, $c1);
        $collection->add(1, $c2);
        $collection->add(3, $c3);

        $iterator = $collection->getIterator();
        $this->assertSame([$c2, $c1, $c3], iterator_to_array($iterator));
    }
}
