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

namespace Teknoo\Tests\East\Paas\Compilation\CompiledDeployment;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheck;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheckType;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheck
 * @covers \Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheckType
 */
class HealthCheckTest extends TestCase
{
    private function buildObject(): HealthCheck
    {
        return new HealthCheck(
            initialDelay: 12,
            period: 24,
            type: HealthCheckType::Command,
            command: ['foo'],
            port: 8080,
            path: '/foo',
        );
    }

    public function testGetInitialDelay()
    {
        self::assertEquals(
            12,
            $this->buildObject()->getInitialDelay(),
        );
    }

    public function testGetPeriod()
    {
        self::assertEquals(
            24,
            $this->buildObject()->getPeriod(),
        );
    }

    public function testGetType()
    {
        self::assertEquals(
            HealthCheckType::Command,
            $this->buildObject()->getType(),
        );
    }

    public function testGetCommand()
    {
        self::assertEquals(
            ['foo'],
            $this->buildObject()->getCommand(),
        );
    }

    public function testGetPort()
    {
        self::assertEquals(
            8080,
            $this->buildObject()->getPort(),
        );
    }

    public function testGetPath()
    {
        self::assertEquals(
            '/foo',
            $this->buildObject()->getPath(),
        );
    }
}
