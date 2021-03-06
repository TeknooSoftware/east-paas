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

namespace Teknoo\Tests\East\Paas\Infrastructures\Laminas\Recipe\Step\History;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Paas\Infrastructures\Laminas\Recipe\Step\History\SendHistory;
use Teknoo\East\Paas\Object\History;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Laminas\Recipe\Step\History\SendHistory
 */
class SendHistoryTest extends TestCase
{
    public function testInvoke()
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('acceptResponse');

        self::assertInstanceOf(
            SendHistory::class,
            (new SendHistory())(
                $client,
                $this->createMock(History::class),
            )
        );
    }
}
