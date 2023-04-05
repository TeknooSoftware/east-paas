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

namespace Teknoo\Tests\East\Paas\Recipe\Step\Misc;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Liveness\TimeoutService;
use Teknoo\East\Foundation\Liveness\TimeoutServiceInterface;
use Teknoo\East\Paas\Recipe\Step\Misc\SetTimeLimit;

use function set_time_limit;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Recipe\Step\Misc\SetTimeLimit
 */
class SetTimeLimitTest extends TestCase
{
    public function buildStep(): SetTimeLimit
    {
        $mock = $this->createMock(TimeoutServiceInterface::class);
        return new SetTimeLimit(
            $mock,
            5*60
        );
    }

    public function testInvoke()
    {
        self::assertInstanceOf(
            SetTimeLimit::class,
            $this->buildStep()()
        );
        set_time_limit(0);
    }
}
