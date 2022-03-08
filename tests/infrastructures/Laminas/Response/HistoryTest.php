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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Laminas\Response;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\Laminas\Response\History;
use Teknoo\East\Paas\Object\History as BaseHistory;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Laminas\Response\History
 */
class HistoryTest extends TestCase
{
    private function build(): History
    {
        return new History(
            200,
            'foo',
            new BaseHistory(null, 'foo', new \DateTime('2021-06-25'))
        );
    }

    public function testToString()
    {
        self::assertEquals(
            'foo',
            (string) $this->build()
        );
    }

    public function testToJson()
    {
        self::assertEquals(
            [
                'message' => 'foo',
                'date' => '2021-06-25 00:00:00 UTC',
                'is_final' => false,
                'extra' => [],
                'previous' => null,
            ],
            \json_decode(\json_encode($this->build()), true)
        );
    }

    public function testGetHistory()
    {
        self::assertInstanceOf(
            BaseHistory::class,
            $this->build()->getHistory()
        );
    }

    public function testGetStatusCode()
    {
        self::assertEquals(
            200,
            $this->build()->getStatusCode()
        );
    }

    public function testGetReasonPhrase()
    {
        self::assertEquals(
            'foo',
            $this->build()->getReasonPhrase()
        );
    }

    public function testWithStatus()
    {
        $response1 = $this->build();
        $response2 = $response1->withStatus(201, 'bar');

        self::assertNotSame(
            $response1,
            $response2
        );

        self::assertInstanceOf(
            History::class,
            $response2
        );

        self::assertEquals(
            200,
            $response1->getStatusCode()
        );

        self::assertEquals(
            'foo',
            $response1->getReasonPhrase()
        );

        self::assertEquals(
            201,
            $response2->getStatusCode()
        );

        self::assertEquals(
            'bar',
            $response2->getReasonPhrase()
        );
    }
}
