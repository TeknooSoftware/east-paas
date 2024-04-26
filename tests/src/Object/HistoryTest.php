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

namespace Teknoo\Tests\East\Paas\Object;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\Tests\East\Common\Object\Traits\ObjectTestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Object\History
 */
class HistoryTest extends TestCase
{
    use ObjectTestTrait;

    /**
     * @return History
     * @throws \Exception
     */
    public function buildObject(): History
    {
        return new History(new History(null, 'foo', new \DateTimeImmutable('2018-04-01')), 'fooBar', new \DateTimeImmutable('2018-05-01'), true, ['foo'=>'bar']);
    }

    public function testGetMessage()
    {
        self::assertEquals(
            'fooBar',
            $this->buildObject()->getMessage()
        );
    }

    public function testGetDate()
    {
        self::assertEquals(
            new \DateTimeImmutable('2018-05-01'),
            $this->buildObject()->getDate()
        );
    }

    public function testGetExtra()
    {
        self::assertEquals(
            ['foo'=>'bar'],
            $this->buildObject()->getExtra()
        );
    }

    public function testGetPrevious()
    {
        self::assertInstanceOf(
            History::class,
            $this->buildObject()->getPrevious()
        );
        self::assertEquals(
            'foo',
            $this->buildObject()->getPrevious()->getMessage()
        );
        self::assertNull(
            $this->buildObject()->getPrevious()->getPrevious()
        );
    }

    public function testIsFinal()
    {
        self::assertTrue($this->buildObject()->isFinal());

        self::assertInstanceOf(
            History::class,
            $this->buildObject()->getPrevious()
        );

        self::assertFalse($this->buildObject()->getPrevious()->isFinal());
    }

    public function testJsonSerialize()
    {
        self::assertEquals(
            '{"message":"bar","date":"2018-05-01 00:00:00 UTC","is_final":true,"extra":[],"previous":{"message":"foo","date":"2018-04-01 00:00:00 UTC","is_final":false,"extra":{"foo":"bar"},"previous":null,"serial_number":123},"serial_number":0}',
            \json_encode(
                value: new History(
                    new History(
                        null,
                        'foo',
                        new \DateTimeImmutable('2018-04-01'),
                        false,
                        ['foo'=>'bar'],
                        123,
                    ),
                    'bar',
                    new \DateTimeImmutable('2018-05-01'),
                    true,
                    serialNumber: 0,
                ),
                flags: JSON_THROW_ON_ERROR
            )
        );
    }

    public function testCloneBadPrevious()
    {
        $this->expectException(\TypeError::class);
        (new History(null, 'foo', new \DateTime('2018-11-25')))->clone(new \stdClass());
    }

    public function testCloneWithoutParent()
    {
        $history = new History(null, 'foo', new \DateTime('2018-11-25'));
        $cloned = $history->clone(null);

        self::assertNotSame($history, $cloned);
        self::assertEquals($history, $cloned);
    }

    public function testCloneWithoutParentWithParent()
    {
        $parent = new History(null, 'bar', new \DateTime('2018-10-25'), serialNumber: 0,);
        $history = new History(null, 'foo', new \DateTime('2018-11-25'), serialNumber: 0,);
        $expected = new History($parent, 'foo', new \DateTime('2018-11-25'), serialNumber: 0,);
        $cloned = $history->clone($parent);

        self::assertNotSame($history, $cloned);
        self::assertNotSame($parent, $cloned);
        self::assertEquals($expected, $cloned);
    }

    public function testCloneWithoutParentWithMoreRecent()
    {
        $recent = new History(null, 'bar', new \DateTime('2019-10-25'), serialNumber: 0,);
        $history = new History(null, 'foo', new \DateTime('2018-11-25'), serialNumber: 0,);
        $expected = new History(
            $history,
            'bar',
            new \DateTime('2019-10-25'),
            serialNumber: 0,
        );

        $cloned = $history->clone($recent);

        self::assertNotSame($history, $cloned);
        self::assertNotSame($recent, $cloned);
        self::assertEquals($expected, $cloned);
    }

    public function testCloneWithNewHistoryToInsert()
    {
        $newHistory = new History(null, 'bar', new \DateTime('2019-10-25'), serialNumber: 0,);
        $history1 = new History(null, 'foo1', new \DateTime('2017-11-25'), serialNumber: 0,);
        $history2 = new History($history1, 'foo2', new \DateTime('2018-11-25'), serialNumber: 0,);
        $history3 = new History($history2, 'foo3', new \DateTime('2020-11-25'), serialNumber: 0,);

        $expected = new History(
            new History(
                new History(
                    new History(
                        null,
                        'foo1',
                        new \DateTime('2017-11-25'),
                        serialNumber: 0,
                    ),
                    'foo2',
                    new \DateTime('2018-11-25'),
                    serialNumber: 0,
                ),
                'bar',
                new \DateTime('2019-10-25'),
                serialNumber: 0,
            ),
            'foo3',
            new \DateTime('2020-11-25'),
            serialNumber: 0,
        );

        $cloned = $history3->clone($newHistory);

        self::assertNotSame($history1, $cloned);
        self::assertNotSame($history2, $cloned);
        self::assertNotSame($history3, $cloned);
        self::assertNotSame($newHistory, $cloned);
        self::assertEquals($expected, $cloned);
    }

    public function testCloneWithNewHistoryToInsert2()
    {
        $newHistory = new History(null, 'bar', new \DateTime('2018-10-25'), serialNumber: 0,);
        $history1 = new History(null, 'foo1', new \DateTime('2017-11-25'), serialNumber: 0,);
        $history2 = new History($history1, 'foo2', new \DateTime('2019-11-25'), serialNumber: 0,);
        $history3 = new History($history2, 'foo3', new \DateTime('2020-11-25'), serialNumber: 0,);

        $expected = new History(
            new History(
                new History(
                    new History(
                        null,
                        'foo1',
                        new \DateTime('2017-11-25'),
                        serialNumber: 0,
                    ),
                    'bar',
                    new \DateTime('2018-10-25'),
                    serialNumber: 0,
                ),
                'foo2',
                new \DateTime('2019-11-25'),
                serialNumber: 0,
            ),
            'foo3',
            new \DateTime('2020-11-25'),
            serialNumber: 0,
        );

        $cloned = $history3->clone($newHistory);

        self::assertNotSame($history1, $cloned);
        self::assertNotSame($history2, $cloned);
        self::assertNotSame($history3, $cloned);
        self::assertNotSame($newHistory, $cloned);
        self::assertEquals($expected, $cloned);
    }

    public function testCloneWithoutParentWithMoreRecentWithCounter()
    {
        $recent = new History(
            previous: null,
            message: 'bar',
            date: new \DateTime('2019-10-25'),
            serialNumber: 4,
        );

        $history = new History(
            previous: null,
            message: 'foo',
            date: new \DateTime('2018-11-25'),
            serialNumber: 3,
        );

        $expected = new History(
            previous: $history,
            message: 'bar',
            date: new \DateTime('2019-10-25'),
            serialNumber: 4,
        );

        $cloned = $history->clone($recent);

        self::assertNotSame($history, $cloned);
        self::assertNotSame($recent, $cloned);
        self::assertEquals($expected, $cloned);
    }

    public function testCloneWithNewHistoryToInsertWithCounter()
    {
        $newHistory = new History(
            previous: null,
            message: 'bar',
            date: new \DateTime('2020-11-25'),
            serialNumber: 3,
        );
        $history1 = new History(
            previous: null,
            message: 'foo1',
            date: new \DateTime('2020-11-25'),
            serialNumber: 1,
        );
        $history2 = new History(
            previous: $history1,
            message: 'foo2',
            date: new \DateTime('2020-11-25'),
            serialNumber: 2,
        );
        $history3 = new History(
            previous: $history2,
            message: 'foo3',
            date: new \DateTime('2020-11-25'),
            serialNumber: 5,
        );

        $expected = new History(
            previous: new History(
                previous: new History(
                    previous: new History(
                        previous:null,
                        message: 'foo1',
                        date: new \DateTime('2020-11-25'),
                        serialNumber: 1,
                    ),
                    message: 'foo2',
                    date: new \DateTime('2020-11-25'),
                    serialNumber: 2,
                ),
                message: 'bar',
                date: new \DateTime('2020-11-25'),
                serialNumber: 3,
            ),
            message: 'foo3',
            date: new \DateTime('2020-11-25'),
            serialNumber: 5,
        );

        $cloned = $history3->clone($newHistory);

        self::assertNotSame($history1, $cloned);
        self::assertNotSame($history2, $cloned);
        self::assertNotSame($history3, $cloned);
        self::assertNotSame($newHistory, $cloned);
        self::assertEquals($expected, $cloned);
    }

    public function testCloneWithNewHistoryToInsert2WithCounter()
    {
        $newHistory = new History(
            previous:null,
            message: 'bar',
            date: new \DateTime('2018-10-25'),
            serialNumber: 0,
        );
        $history1 = new History(
            previous:null,
            message: 'foo1',
            date: new \DateTime('2017-11-25'),
            serialNumber: 1,
        );
        $history2 = new History(
            previous:$history1,
            message: 'foo2',
            date: new \DateTime('2019-11-25'),
            serialNumber: 0,
        );
        $history3 = new History(
            previous:$history2,
            message: 'foo3',
            date: new \DateTime('2020-11-25'),
            serialNumber: 5,
        );
        $history4 = new History(
            previous:$history3,
            message: 'foo4',
            date: new \DateTime('2021-11-25'),
            serialNumber: 0,
        );
        $history5 = new History(
            previous:$history4,
            message: 'foo5',
            date: new \DateTime('2021-11-25'),
            serialNumber: 5,
        );

        $expected = new History(
            previous: new History(
                previous: new History(
                    previous: new History(
                        previous: new History(
                            previous: new History(
                                previous: null,
                                message: 'foo1',
                                date: new \DateTime('2017-11-25'),
                                serialNumber: 1,
                            ),
                            message: 'bar',
                            date: new \DateTime('2018-10-25'),
                            serialNumber: 0,
                        ),
                        message: 'foo2',
                        date: new \DateTime('2019-11-25'),
                        serialNumber: 0,
                    ),
                    message: 'foo3',
                    date: new \DateTime('2020-11-25'),
                    serialNumber: 5,
                ),
                message: 'foo4',
                date: new \DateTime('2021-11-25'),
                serialNumber: 0,
            ),
            message: 'foo5',
            date: new \DateTime('2021-11-25'),
            serialNumber: 5,
        );

        $cloned = $history5->clone($newHistory);

        self::assertNotSame($history1, $cloned);
        self::assertNotSame($history2, $cloned);
        self::assertNotSame($history3, $cloned);
        self::assertNotSame($newHistory, $cloned);
        self::assertEquals($expected, $cloned);
    }

    public function testCloneWithNewHistoryToInsertWithCounterAndFinal()
    {
        $recent = new History(
            previous: null,
            message: 'bar',
            date: new \DateTime('2019-10-25'),
            serialNumber: 1,
        );

        $history = new History(
            previous: null,
            message: 'foo',
            date: new \DateTime('2018-11-25'),
            serialNumber: 3,
            isFinal: true,
        );

        $expected = new History(
            previous: $recent,
            message: 'foo',
            date: new \DateTime('2018-11-25'),
            serialNumber: 3,
            isFinal: true,
        );

        $cloned = $history->clone($recent);

        self::assertNotSame($history, $cloned);
        self::assertNotSame($recent, $cloned);
        self::assertEquals($expected, $cloned);

        $recent = new History(
            previous: null,
            message: 'bar',
            date: new \DateTime('2019-10-25'),
            serialNumber: 1,
            isFinal: true,
        );

        $history = new History(
            previous: null,
            message: 'foo',
            date: new \DateTime('2018-11-25'),
            serialNumber: 3,
        );

        $expected = new History(
            previous: $history,
            message: 'bar',
            date: new \DateTime('2019-10-25'),
            serialNumber: 1,
            isFinal: true,
        );

        $cloned = $history->clone($recent);

        self::assertNotSame($history, $cloned);
        self::assertNotSame($recent, $cloned);
        self::assertEquals($expected, $cloned);
    }
}
