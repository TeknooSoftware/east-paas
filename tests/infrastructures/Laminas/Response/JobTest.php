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

namespace Teknoo\Tests\East\Paas\Infrastructures\Laminas\Response;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\Laminas\Response\Job;
use Teknoo\East\Paas\Object\Job as BaseJob;

use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Job::class)]
class JobTest extends TestCase
{
    private function build(): Job
    {
        return new Job(
            200,
            'foo',
            new BaseJob(),
            json_encode(value: ['foo' => 'bar', 'bar' => 'foo'], flags: JSON_THROW_ON_ERROR)
        );
    }

    public function testToString(): void
    {
        $this->assertEquals('{"foo":"bar","bar":"foo"}', (string) $this->build());
    }

    public function testGetJob(): void
    {
        $this->assertInstanceOf(BaseJob::class, $this->build()->getJob());
    }

    public function testGetStatusCode(): void
    {
        $this->assertEquals(200, $this->build()->getStatusCode());
    }

    public function testGetReasonPhrase(): void
    {
        $this->assertEquals('foo', $this->build()->getReasonPhrase());
    }

    public function testWithStatus(): void
    {
        $response1 = $this->build();
        $response2 = $response1->withStatus(201, 'bar');

        $this->assertNotSame($response1, $response2);

        $this->assertInstanceOf(Job::class, $response2);

        $this->assertEquals(200, $response1->getStatusCode());

        $this->assertEquals('foo', $response1->getReasonPhrase());

        $this->assertEquals(201, $response2->getStatusCode());

        $this->assertEquals('bar', $response2->getReasonPhrase());
    }
}
