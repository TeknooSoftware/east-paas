<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Hook;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\East\Paas\Hook\HooksCollection;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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