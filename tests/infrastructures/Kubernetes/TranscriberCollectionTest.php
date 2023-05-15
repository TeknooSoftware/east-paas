<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Kubernetes;


use PHPUnit\Framework\TestCase;

use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\TranscriberCollection;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\TranscriberCollection
 */
class TranscriberCollectionTest extends TestCase
{
    public function testAdd()
    {
        self::assertInstanceOf(
            TranscriberCollection::class,
            (new TranscriberCollection())->add(
                1,
                $this->createMock(TranscriberInterface::class)
            )
        );
    }

    public function testGetIterator()
    {
        $collection = new TranscriberCollection();

        $c1 = $this->createMock(TranscriberInterface::class);
        $c2 = $this->createMock(TranscriberInterface::class);
        $c3 = $this->createMock(TranscriberInterface::class);

        $collection->add(2, $c1);
        $collection->add(1, $c2);
        $collection->add(3, $c3);

        $iterator = $collection->getIterator();
        self::assertSame(
            [$c2, $c1, $c3],
            \iterator_to_array($iterator)
        );
    }
}
