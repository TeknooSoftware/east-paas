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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Compilation\CompiledDeployment;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheck;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheckType;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(HealthCheckType::class)]
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

    public function testIsSecure()
    {
        self::assertTrue(
            $this->buildObject()->isSecure(),
        );
    }

    public function testGetSuccessThreshold()
    {
        self::assertEquals(
            23,
            $this->buildObject()->getSuccessThreshold(),
        );
    }

    public function testGetFailureThreshold()
    {
        self::assertEquals(
            45,
            $this->buildObject()->getFailureThreshold(),
        );
    }
}
