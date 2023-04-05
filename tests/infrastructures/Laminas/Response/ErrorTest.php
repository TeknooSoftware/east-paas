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
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Laminas\Response;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\Laminas\Response\Error;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Infrastructures\Laminas\Response\Error
 */
class ErrorTest extends TestCase
{
    private function build(): Error
    {
        return new Error(
            500,
            'foo',
            new \RuntimeException('bar', 500)
        );
    }

    public function testToString()
    {
        self::assertEquals(
            'foo (500)',
            (string) $this->build()
        );
    }

    public function testToJson()
    {
        self::assertEquals(
            [
                'type' => 'https://teknoo.software/probs/issue',
                'title' => 'foo',
                'status' => 500,
                'detail' => ['bar'],
            ],
            \json_decode(\json_encode($this->build(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    public function testGetError()
    {
        self::assertInstanceOf(
            \Throwable::class,
            $this->build()->getError()
        );
    }

    public function testGetStatusCode()
    {
        self::assertEquals(
            500,
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
        $response2 = $response1->withStatus(501, 'bar');

        self::assertNotSame(
            $response1,
            $response2
        );

        self::assertInstanceOf(
            Error::class,
            $response2
        );

        self::assertEquals(
            500,
            $response1->getStatusCode()
        );

        self::assertEquals(
            'foo',
            $response1->getReasonPhrase()
        );

        self::assertEquals(
            501,
            $response2->getStatusCode()
        );

        self::assertEquals(
            'bar',
            $response2->getReasonPhrase()
        );
    }
}
