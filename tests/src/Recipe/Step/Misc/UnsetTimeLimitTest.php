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

namespace Teknoo\Tests\East\Paas\Recipe\Step\Misc;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Liveness\TimeoutService;
use Teknoo\East\Foundation\Liveness\TimeoutServiceInterface;
use Teknoo\East\Paas\Recipe\Step\Misc\UnsetTimeLimit;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Recipe\Step\Misc\UnsetTimeLimit
 */
class UnsetTimeLimitTest extends TestCase
{
    public function buildStep(): UnsetTimeLimit
    {
        $mock = $this->createMock(TimeoutServiceInterface::class);
        return new UnsetTimeLimit(
            $mock
        );
    }

    public function testInvoke()
    {
        self::assertInstanceOf(
            UnsetTimeLimit::class,
            $this->buildStep()()
        );
    }
}
