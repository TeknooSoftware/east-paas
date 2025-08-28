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

namespace Teknoo\Tests\East\Paas\Compilation\CompiledDeployment\Expose;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Service;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Transport;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Service::class)]
class ServiceTest extends TestCase
{
    private function buildObject(bool $internal = false): Service
    {
        return new Service('foo', 'bar', [80 => 8080], Transport::Tcp, $internal);
    }

    public function testGetName(): void
    {
        $this->assertEquals('foo', $this->buildObject()->getName());
    }

    public function testGetPodName(): void
    {
        $this->assertEquals('bar', $this->buildObject()->getPodName());
    }

    public function testGetPorts(): void
    {
        $this->assertEquals([80 => 8080], $this->buildObject()->getPorts());
    }

    public function testGetProtocol(): void
    {
        $this->assertEquals(Transport::Tcp, $this->buildObject()->getProtocol());
    }

    public function testIsInternal(): void
    {
        $this->assertFalse($this->buildObject()->isInternal());

        $this->assertTrue($this->buildObject(true)->isInternal());
    }
}
