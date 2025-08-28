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
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod\RestartPolicy;
use Teknoo\East\Paas\Compilation\CompiledDeployment\UpgradeStrategy;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Pod::class)]
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
            isStateless: false,
            restartPolicy: RestartPolicy::Never,
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals('foo', $this->buildObject()->getName());
    }

    public function testGetReplicas(): void
    {
        $this->assertEquals('2', $this->buildObject()->getReplicas());
    }

    public function testGetOciRegistryConfigName(): void
    {
        $this->assertEquals('bar', $this->buildObject()->getOciRegistryConfigName());
    }

    public function testGetMaxUpgradingPods(): void
    {
        $this->assertEquals(3, $this->buildObject()->getMaxUpgradingPods());
    }

    public function testGetMaxUnavailablePods(): void
    {
        $this->assertEquals(2, $this->buildObject()->getMaxUnavailablePods());
    }

    public function testGetUpgradeStrategy(): void
    {
        $this->assertEquals(UpgradeStrategy::RollingUpgrade, $this->buildObject()->getUpgradeStrategy());
    }

    public function testGetFsGroup(): void
    {
        $this->assertEquals(123, $this->buildObject()->getFsGroup());
    }


    public function testGetRequires(): void
    {
        $this->assertEquals(['foo', 'bar'], $this->buildObject()->getRequires());
    }

    public function testGetIterator(): void
    {
        $this->assertContainsOnlyInstancesOf(Container::class, $this->buildObject());
    }

    public function testIsStateless(): void
    {
        $this->assertIsBool($this->buildObject()->isStateless());
    }

    public function testGetRestartPolicy(): void
    {
        $this->assertEquals(RestartPolicy::Never, $this->buildObject()->getRestartPolicy());
    }
}
