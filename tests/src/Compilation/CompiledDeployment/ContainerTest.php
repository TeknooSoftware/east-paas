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

namespace Teknoo\Tests\East\Paas\Compilation\CompiledDeployment;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Container;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheck;
use Teknoo\East\Paas\Compilation\CompiledDeployment\ResourceSet;

/**
 * @license     http://teknoo.software/license/mit         MIT License
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

    public function testGetName()
    {
        self::assertEquals(
            'foo',
            $this->buildObject()->getName(),
        );
    }

    public function testGetImage()
    {
        self::assertEquals(
            'bar',
            $this->buildObject()->getImage(),
        );
    }

    public function testGetVersion()
    {
        self::assertEquals(
            '1.2',
            $this->buildObject()->getVersion(),
        );
    }

    public function testGetListen()
    {
        self::assertEquals(
            [80],
            $this->buildObject()->getListen(),
        );
    }

    public function testGetVolumes()
    {
        self::assertEquals(
            ['foo', 'bar'],
            $this->buildObject()->getVolumes(),
        );
    }

    public function testGetVariables()
    {
        self::assertEquals(
            ['bar' => 'foo'],
            $this->buildObject()->getVariables(),
        );
    }

    public function testGetHealthcheck()
    {
        self::assertInstanceOf(
            HealthCheck::class,
            $this->buildObject()->getHealthCheck(),
        );
    }

    public function testGetResources()
    {
        self::assertInstanceOf(
            ResourceSet::class,
            $this->buildObject()->getResources(),
        );
    }
}