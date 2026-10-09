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

namespace Teknoo\Tests\East\Paas\Compilation\CompiledDeployment\Job;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\ConcurrencyPolicy;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\ScheduleOptions;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ScheduleOptions::class)]
class ScheduleOptionsTest extends TestCase
{
    public function testAccess(): void
    {
        $options = new ScheduleOptions(
            timeZone: 'Europe/Paris',
            concurrency: ConcurrencyPolicy::Forbid,
            startingDeadline: 300,
            successfulHistory: 3,
            failedHistory: 1,
            suspend: false,
        );

        $this->assertEquals('Europe/Paris', $options->timeZone);
        $this->assertEquals(ConcurrencyPolicy::Forbid, $options->concurrency);
        $this->assertEquals(300, $options->startingDeadline);
        $this->assertEquals(3, $options->successfulHistory);
        $this->assertEquals(1, $options->failedHistory);
        $this->assertFalse($options->suspend);
    }

    public function testDefaultValues(): void
    {
        $options = new ScheduleOptions();

        $this->assertNull($options->timeZone);
        $this->assertNull($options->concurrency);
        $this->assertNull($options->startingDeadline);
        $this->assertNull($options->successfulHistory);
        $this->assertNull($options->failedHistory);
        $this->assertNull($options->suspend);
    }
}
