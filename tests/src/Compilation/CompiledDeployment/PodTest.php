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
use Teknoo\East\Paas\Compilation\CompiledDeployment\Container;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\UpgradeStrategy;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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

    public function testGetIterator()
    {
        foreach ($this->buildObject() as $container) {
            self::assertInstanceOf(Container::class, $container);
        }
    }
}