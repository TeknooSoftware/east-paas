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

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Container;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\UpgradeStrategy;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Compilation\CompiledDeployment\Pod
 */
class PodTest extends TestCase
{
    private function buildObject(): Pod
    {
        return new Pod(
            name: 'foo',
            replicas: 2,
            containers: [$this->createMock(Container::class)],
            ociRegistryConfigName: 'bar',
            maxUpgradingPods: 3,
            maxUnavailablePods: 2,
            upgradeStrategy: UpgradeStrategy::RollingUpgrade,
            fsGroup: 123,
            requires: ['foo', 'bar'],
        );
    }

    public function testGetName()
    {
        self::assertEquals(
            'foo',
            $this->buildObject()->getName()
        );
    }

    public function testGetReplicas()
    {
        self::assertEquals(
            '2',
            $this->buildObject()->getReplicas()
        );
    }

    public function testGetOciRegistryConfigName()
    {
        self::assertEquals(
            'bar',
            $this->buildObject()->getOciRegistryConfigName()
        );
    }

    public function testGetMaxUpgradingPods()
    {
        self::assertEquals(
            3,
            $this->buildObject()->getMaxUpgradingPods()
        );
    }

    public function testGetMaxUnavailablePods()
    {
        self::assertEquals(
            2,
            $this->buildObject()->getMaxUnavailablePods()
        );
    }

    public function testGetUpgradeStrategy()
    {
        self::assertEquals(
            UpgradeStrategy::RollingUpgrade,
            $this->buildObject()->getUpgradeStrategy()
        );
    }

    public function testGetFsGroup()
    {
        self::assertEquals(
            123,
            $this->buildObject()->getFsGroup()
        );
    }


    public function testGetRequires()
    {
        self::assertEquals(
            ['foo', 'bar'],
            $this->buildObject()->getRequires()
        );
    }

    public function testGetIterator()
    {
        foreach ($this->buildObject() as $container) {
            self::assertInstanceOf(Container::class, $container);
        }
    }
}