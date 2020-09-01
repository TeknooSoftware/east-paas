<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
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
use Teknoo\Tests\East\Website\Object\Traits\ObjectTestTrait;

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
                )
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
        self::assertEquals($expected, $cloned);
    }

    public function testSetDeletedAt()
    {
        self::markTestSkipped('Not implemented');
    }

    public function testSetDeletedAtExceptionOnBadArgument()
    {
        self::markTestSkipped('Not implemented');
    }

    public function testDeletedAt()
    {
        self::markTestSkipped('Not implemented');
    }
}
