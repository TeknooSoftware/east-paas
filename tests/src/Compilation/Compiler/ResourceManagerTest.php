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

namespace Teknoo\Tests\East\Paas\Compilation\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\ResourceSet;
use Teknoo\East\Paas\Compilation\Compiler\Exception\ResourceCapacityExceededException;
use Teknoo\East\Paas\Compilation\Compiler\ResourceManager;
use Teknoo\East\Paas\Contracts\Compilation\Quota\AvailabilityInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ResourceManager::class)]
class ResourceManagerTest extends TestCase
{
    private function buildManager(): ResourceManager
    {
        return new ResourceManager();
    }

    public function testUpdateQuotaAvailabilityNewAvailability()
    {
        self::assertInstanceOf(
            ResourceManager::class,
            $this->buildManager()->updateQuotaAvailability(
                'cpu',
                $this->createMock(AvailabilityInterface::class),
            ),
        );
    }

    public function testUpdateQuotaAvailabilityUpdateAvailability()
    {
        $manager = $this->buildManager();
        $a1 = $this->createMock(AvailabilityInterface::class);
        $a1->expects($this->once())->method('update')->willReturnSelf();
        $a2 = $this->createMock(AvailabilityInterface::class);

        self::assertInstanceOf(
            ResourceManager::class,
            $manager->updateQuotaAvailability(
                'cpu',
                $a1,
            ),
        );

        self::assertInstanceOf(
            ResourceManager::class,
            $manager->updateQuotaAvailability(
                'cpu',
                $a2,
            ),
        );
    }

    public function testUpdateQuotaAvailabilityFreezed()
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

    public function testReserveAvailabilityNotDefined()
    {
        $manager = $this->buildManager();

        $this->expectException(ResourceCapacityExceededException::class);
        $manager->reserve('cpu', '100m', '500m', 1, $this->createMock(ResourceSet::class));
    }

    public function testReserve()
    {
        $manager = $this->buildManager();
        $a = $this->createMock(AvailabilityInterface::class);
        $a->expects($this->once())->method('reserve')->willReturnSelf();

        self::assertInstanceOf(
            ResourceManager::class,
            $manager->updateQuotaAvailability(
                'cpu',
                $a,
            ),
        );

        self::assertInstanceOf(
            ResourceManager::class,
            $manager->reserve('cpu', '100m', '500m', 1, $this->createMock(ResourceSet::class)),
        );
    }

    public function testPrepareAutomaticsReservationsNotDefined()
    {
        $manager = $this->buildManager();
        $set = $this->createMock(ResourceSet::class);
        $set->expects($this->never())->method('add');

        self::assertInstanceOf(
            ResourceManager::class,
            $manager->prepareAutomaticsReservations(
                $set,
                3,
                [
                    'cpu',
                ],
            )
        );

        self::assertInstanceOf(
            ResourceManager::class,
            $manager->computeAutomaticReservations()
        );
    }

    public function testAutomaticsReservations()
    {
        $manager = $this->buildManager();
        $a1 = $this->createMock(AvailabilityInterface::class);
        $a1->expects($this->exactly(1))
            ->method('updateResource')
            ->with(
                $this->callback(fn () => true),
                100
            );
        $a2 = $this->createMock(AvailabilityInterface::class);
        $a2->expects($this->exactly(2))
            ->method('updateResource')
            ->with(
                $this->callback(fn () => true),
                33
            );

        $set = $this->createMock(ResourceSet::class);
        $set->expects($this->exactly(3))->method('add');

        self::assertInstanceOf(
            ResourceManager::class,
            $manager->updateQuotaAvailability(
                'cpu',
                $a1,
            ),
        );

        self::assertInstanceOf(
            ResourceManager::class,
            $manager->updateQuotaAvailability(
                'memory',
                $a2,
            ),
        );

        self::assertInstanceOf(
            ResourceManager::class,
            $manager->prepareAutomaticsReservations(
                $set,
                2,
                [
                    'cpu',
                ],
            )
        );

        self::assertInstanceOf(
            ResourceManager::class,
            $manager->prepareAutomaticsReservations($set, 1, [])
        );

        self::assertInstanceOf(
            ResourceManager::class,
            $manager->computeAutomaticReservations()
        );
    }
}
