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

namespace Teknoo\Tests\East\Paas\Object;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\Tests\East\Common\Object\Traits\ObjectTestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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
            '{"message":"bar","date":"2018-05-01 00:00:00 UTC","is_final":true,"extra":[],"previous":{"message":"foo","date":"2018-04-01 00:00:00 UTC","is_final":false,"extra":{"foo":"bar"},"previous":null}}',
            \json_encode(
                new History(
                    new History(null, 'foo', new \DateTimeImmutable('2018-04-01'), false, ['foo'=>'bar']),
                    'bar',
                    new \DateTimeImmutable('2018-05-01'),
                    true
                ), JSON_THROW_ON_ERROR
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
        $parent = new History(null, 'bar', new \DateTime('2018-10-25'));
        $history = new History(null, 'foo', new \DateTime('2018-11-25'));
        $expected = new History($parent, 'foo', new \DateTime('2018-11-25'));
        $cloned = $history->clone($parent);

        self::assertNotSame($history, $cloned);
        self::assertNotSame($parent, $cloned);
        self::assertEquals($expected, $cloned);
    }

    public function testCloneWithoutParentWithMoreRecent()
    {
        $recent = new History(null, 'bar', new \DateTime('2019-10-25'));
        $history = new History(null, 'foo', new \DateTime('2018-11-25'));
        $expected = new History(
            $history,
            'bar',
            new \DateTime('2019-10-25'),
        );

        $cloned = $history->clone($recent);

        self::assertNotSame($history, $cloned);
        self::assertNotSame($recent, $cloned);
        self::assertEquals($expected, $cloned);
    }

    public function testCloneWithNewHistoryToInsert()
    {
        $newHistory = new History(null, 'bar', new \DateTime('2019-10-25'));
        $history1 = new History(null, 'foo1', new \DateTime('2017-11-25'));
        $history2 = new History($history1, 'foo2', new \DateTime('2018-11-25'));
        $history3 = new History($history2, 'foo3', new \DateTime('2020-11-25'));

        $expected = new History(
            new History(
                new History(
                    new History(
                        null,
                        'foo1',
                        new \DateTime('2017-11-25'),
                    ),
                    'foo2',
                    new \DateTime('2018-11-25'),
                ),
                'bar',
                new \DateTime('2019-10-25'),
            ),
            'foo3',
            new \DateTime('2020-11-25'),
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
        $newHistory = new History(null, 'bar', new \DateTime('2018-10-25'));
        $history1 = new History(null, 'foo1', new \DateTime('2017-11-25'));
        $history2 = new History($history1, 'foo2', new \DateTime('2019-11-25'));
        $history3 = new History($history2, 'foo3', new \DateTime('2020-11-25'));

        $expected = new History(
            new History(
                new History(
                    new History(
                        null,
                        'foo1',
                        new \DateTime('2017-11-25'),
                    ),
                    'bar',
                    new \DateTime('2018-10-25'),
                ),
                'foo2',
                new \DateTime('2019-11-25'),
            ),
            'foo3',
            new \DateTime('2020-11-25'),
        );

        $cloned = $history3->clone($newHistory);

        self::assertNotSame($history1, $cloned);
        self::assertNotSame($history2, $cloned);
        self::assertNotSame($history3, $cloned);
        self::assertNotSame($newHistory, $cloned);
        self::assertEquals($expected, $cloned);
    }
}
