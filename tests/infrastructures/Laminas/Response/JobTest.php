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

namespace Teknoo\Tests\East\Paas\Infrastructures\Laminas\Response;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\Laminas\Response\Job;
use Teknoo\East\Paas\Object\Job as BaseJob;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Laminas\Response\Job
 */
class JobTest extends TestCase
{
    private function build(): Job
    {
        return new Job(
            200,
            'foo',
            new BaseJob(),
            \json_encode(['foo' => 'bar', 'bar' => 'foo'])
        );
    }

    public function testToString()
    {
        self::assertEquals(
            '{"foo":"bar","bar":"foo"}',
            (string) $this->build()
        );
    }

    public function testGetJob()
    {
        self::assertInstanceOf(
            BaseJob::class,
            $this->build()->getJob()
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
            Job::class,
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
