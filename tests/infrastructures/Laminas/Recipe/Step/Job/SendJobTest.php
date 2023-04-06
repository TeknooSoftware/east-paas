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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Laminas\Recipe\Step\Job;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Paas\Infrastructures\Laminas\Recipe\Step\Job\SendJob;
use Teknoo\East\Paas\Object\Job;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Infrastructures\Laminas\Recipe\Step\Job\SendJob
 */
class SendJobTest extends TestCase
{
    public function testInvoke()
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('acceptResponse');

        self::assertInstanceOf(
            SendJob::class,
            (new SendJob())(
                $client,
                $this->createMock(Job::class),
                \json_encode(['foo' => 'bar']),
            )
        );
    }
}
