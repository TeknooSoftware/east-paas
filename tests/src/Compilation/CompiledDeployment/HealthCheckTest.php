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

namespace Teknoo\Tests\East\Paas\Compilation\CompiledDeployment;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheck;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheckType;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(HealthCheck::class)]
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
            isSecure: true,
            successThreshold: 23,
            failureThreshold: 45,
        );
    }

    public function testGetInitialDelay(): void
    {
        $this->assertEquals(12, $this->buildObject()->getInitialDelay());
    }

    public function testGetPeriod(): void
    {
        $this->assertEquals(24, $this->buildObject()->getPeriod());
    }

    public function testGetType(): void
    {
        $this->assertEquals(HealthCheckType::Command, $this->buildObject()->getType());
    }

    public function testGetCommand(): void
    {
        $this->assertEquals(['foo'], $this->buildObject()->getCommand());
    }

    public function testGetPort(): void
    {
        $this->assertEquals(8080, $this->buildObject()->getPort());
    }

    public function testGetPath(): void
    {
        $this->assertEquals('/foo', $this->buildObject()->getPath());
    }

    public function testIsSecure(): void
    {
        $this->assertTrue($this->buildObject()->isSecure());
    }

    public function testGetSuccessThreshold(): void
    {
        $this->assertEquals(23, $this->buildObject()->getSuccessThreshold());
    }

    public function testGetFailureThreshold(): void
    {
        $this->assertEquals(45, $this->buildObject()->getFailureThreshold());
    }
}
