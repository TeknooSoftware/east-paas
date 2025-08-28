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
use Teknoo\East\Paas\Compilation\CompiledDeployment\Container;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheck;
use Teknoo\East\Paas\Compilation\CompiledDeployment\ResourceSet;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Container::class)]
class ContainerTest extends TestCase
{
    private function buildObject(): Container
    {
        return new Container(
            name: 'foo',
            image: 'bar',
            version: '1.2',
            listen: [80],
            volumes: ['foo', 'bar'],
            variables: ['bar' => 'foo'],
            healthCheck: $this->createMock(HealthCheck::class),
            resources: new ResourceSet(),
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals('foo', $this->buildObject()->getName());
    }

    public function testGetImage(): void
    {
        $this->assertEquals('bar', $this->buildObject()->getImage());
    }

    public function testGetVersion(): void
    {
        $this->assertEquals('1.2', $this->buildObject()->getVersion());
    }

    public function testGetListen(): void
    {
        $this->assertEquals([80], $this->buildObject()->getListen());
    }

    public function testGetVolumes(): void
    {
        $this->assertEquals(['foo', 'bar'], $this->buildObject()->getVolumes());
    }

    public function testGetVariables(): void
    {
        $this->assertEquals(['bar' => 'foo'], $this->buildObject()->getVariables());
    }

    public function testGetHealthcheck(): void
    {
        $this->assertInstanceOf(HealthCheck::class, $this->buildObject()->getHealthCheck());
    }

    public function testGetResources(): void
    {
        $this->assertInstanceOf(ResourceSet::class, $this->buildObject()->getResources());
    }
}
