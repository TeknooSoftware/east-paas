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

namespace Teknoo\Tests\East\Paas\Compilation\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\ResourceSet;
use Teknoo\East\Paas\Compilation\Compiler\Exception\ResourceCapacityExceededException;
use Teknoo\East\Paas\Compilation\Compiler\ResourceManager;
use Teknoo\East\Paas\Contracts\Compilation\Quota\AvailabilityInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ResourceManager::class)]
class ResourceManagerTest extends TestCase
{
    private function buildManager(): ResourceManager
    {
        return new ResourceManager();
    }

    public function testUpdateQuotaAvailabilityNewAvailability(): void
    {
        $this->assertInstanceOf(ResourceManager::class, $this->buildManager()->updateQuotaAvailability(
            'cpu',
            $this->createMock(AvailabilityInterface::class),
        ));
    }

    public function testUpdateQuotaAvailabilityUpdateAvailability(): void
    {
        $manager = $this->buildManager();
        $a1 = $this->createMock(AvailabilityInterface::class);
        $a1->expects($this->once())->method('update')->willReturnSelf();
        $a2 = $this->createMock(AvailabilityInterface::class);

        $this->assertInstanceOf(ResourceManager::class, $manager->updateQuotaAvailability(
            'cpu',
            $a1,
        ));

        $this->assertInstanceOf(ResourceManager::class, $manager->updateQuotaAvailability(
            'cpu',
            $a2,
        ));
    }

    public function testUpdateQuotaAvailabilityFreezed(): void
    {
        $manager = $this->buildManager();
        $a = $this->createMock(AvailabilityInterface::class);

        $manager->freeze();
        $this->expectException(ResourceCapacityExceededException::class);
        $manager->updateQuotaAvailability(
            'cpu',
            $a,
        );
    }

    public function testReserveAvailabilityNotDefined(): void
    {
        $manager = $this->buildManager();

        $this->expectException(ResourceCapacityExceededException::class);
        $manager->reserve('cpu', '100m', '500m', 1, $this->createMock(ResourceSet::class));
    }

    public function testReserve(): void
    {
        $manager = $this->buildManager();
        $a = $this->createMock(AvailabilityInterface::class);
        $a->expects($this->once())->method('reserve')->willReturnSelf();

        $this->assertInstanceOf(ResourceManager::class, $manager->updateQuotaAvailability(
            'cpu',
            $a,
        ));

        $this->assertInstanceOf(ResourceManager::class, $manager->reserve('cpu', '100m', '500m', 1, $this->createMock(ResourceSet::class)));
    }

    public function testPrepareAutomaticsReservationsNotDefined(): void
    {
        $manager = $this->buildManager();
        $set = $this->createMock(ResourceSet::class);
        $set->expects($this->never())->method('add');

        $this->assertInstanceOf(ResourceManager::class, $manager->prepareAutomaticsReservations(
            $set,
            3,
            [
                'cpu',
            ],
        ));

        $this->assertInstanceOf(ResourceManager::class, $manager->computeAutomaticReservations());
    }

    public function testAutomaticsReservations(): void
    {
        $manager = $this->buildManager();
        $a1 = $this->createMock(AvailabilityInterface::class);
        $a1->expects($this->exactly(1))
            ->method('updateResource')
            ->with(
                $this->callback(fn (): true => true),
                100
            );
        $a2 = $this->createMock(AvailabilityInterface::class);
        $a2->expects($this->exactly(2))
            ->method('updateResource')
            ->with(
                $this->callback(fn (): true => true),
                33
            );

        $set = $this->createMock(ResourceSet::class);
        $set->expects($this->exactly(3))->method('add');

        $this->assertInstanceOf(ResourceManager::class, $manager->updateQuotaAvailability(
            'cpu',
            $a1,
        ));

        $this->assertInstanceOf(ResourceManager::class, $manager->updateQuotaAvailability(
            'memory',
            $a2,
        ));

        $this->assertInstanceOf(ResourceManager::class, $manager->prepareAutomaticsReservations(
            $set,
            2,
            [
                'cpu',
            ],
        ));

        $this->assertInstanceOf(ResourceManager::class, $manager->prepareAutomaticsReservations($set, 1, []));

        $this->assertInstanceOf(ResourceManager::class, $manager->computeAutomaticReservations());
    }
}
