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

namespace Teknoo\Tests\East\Paas\Compilation\Compiler\Quota;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\AutomaticResource;
use Teknoo\East\Paas\Compilation\CompiledDeployment\ResourceSet;
use Teknoo\East\Paas\Compilation\Compiler\Exception\QuotaWrongConfigurationException;
use Teknoo\East\Paas\Compilation\Compiler\Exception\ResourceCapacityExceededException;
use Teknoo\East\Paas\Compilation\Compiler\Exception\QuotasNotCompliantException;
use Teknoo\East\Paas\Compilation\Compiler\Exception\ResourceWrongConfigurationException;
use Teknoo\East\Paas\Contracts\Compilation\Quota\AvailabilityInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
abstract class AbstractTestAvailability extends TestCase
{
    abstract protected function createAvailability(string $capacity, bool $isSoft): AvailabilityInterface;

    abstract protected function getDefaultCapacity(): string;

    abstract protected function getLargerCapacity(): string;

    abstract protected function getSmallerCapacity(): string;

    abstract protected function getReserveValueCapacity(): string;

    public function testConstructionErrorIfCapacityIsRelativeForNonSoft()
    {
        $this->expectException(QuotaWrongConfigurationException::class);
        $this->createAvailability('100%', false);
    }

    public function testConstructionNoErrorIfCapacityIsRelativeForNonSoft()
    {
        self::assertInstanceOf(
            AvailabilityInterface::class,
            $this->createAvailability('100%', true)
        );
    }

    public function testGetCapacity()
    {
        self::assertEquals(
            $this->getDefaultCapacity(),
            $this->createAvailability($this->getDefaultCapacity(), false)->getCapacity(),
        );
    }

    public function testUpdateWrongClass()
    {
        $this->expectException(QuotasNotCompliantException::class);
        $this->createAvailability($this->getDefaultCapacity(), false)->update(
            $this->createMock(AvailabilityInterface::class),
        );
    }

    public function testUpdateWithLargerCapacity()
    {
        $this->expectException(ResourceCapacityExceededException::class);
        $this->createAvailability($this->getDefaultCapacity(), false)->update(
            $this->createAvailability($this->getLargerCapacity(), false),
        );
    }

    public function testUpdate()
    {
        self::assertEquals(
            $this->getSmallerCapacity(),
            $this->createAvailability($this->getDefaultCapacity(), false)->update(
                $this->createAvailability($this->getSmallerCapacity(), false),
            )->getCapacity()
        );
    }

    public function testReserveLimitSmallerThanRequire()
    {
        $set = $this->createMock(ResourceSet::class);
        $set->expects(self::never())
            ->method('add');

        $this->expectException(ResourceWrongConfigurationException::class);
        $this->createAvailability($this->getDefaultCapacity(), false)->reserve(
            require: '500',
            limit: '100',
            numberOfReplicas: 1,
            set: $set,
        );
    }

    public function testReserveLimitBiggerThanCapacity()
    {
        $set = $this->createMock(ResourceSet::class);
        $set->expects(self::never())
            ->method('add');

        $this->expectException(ResourceCapacityExceededException::class);
        $this->createAvailability($this->getDefaultCapacity(), false)->reserve(
            require: $this->getLargerCapacity(),
            limit: $this->getLargerCapacity(),
            numberOfReplicas: 1,
            set: $set,
        );
    }

    public function testReserveLimitBiggerThanCapacityWithSoftQuota()
    {
        $set = $this->createMock(ResourceSet::class);
        $set->expects(self::never())
            ->method('add');

        $this->expectException(ResourceCapacityExceededException::class);
        $this->createAvailability($this->getDefaultCapacity(), true)->reserve(
            require: $this->getLargerCapacity(),
            limit: $this->getLargerCapacity(),
            numberOfReplicas: 1,
            set: $set,
        );
    }

    public function testReserve()
    {
        $set = $this->createMock(ResourceSet::class);
        $set->expects(self::once())
            ->method('add');

        self::assertInstanceOf(
            AvailabilityInterface::class,
            $this->createAvailability($this->getDefaultCapacity(), false)
                ->reserve(
                    require: $this->getReserveValueCapacity(),
                    limit: $this->getReserveValueCapacity(),
                    numberOfReplicas: 1,
                    set: $set,
                )
        );
    }

    public function testReservePourcent()
    {
        $set = $this->createMock(ResourceSet::class);
        $set->expects(self::once())
            ->method('add');

        self::assertInstanceOf(
            AvailabilityInterface::class,
            $this->createAvailability($this->getDefaultCapacity(), false)
                ->reserve(
                    require: '5%',
                    limit: '50%',
                    numberOfReplicas: 1,
                    set: $set,
                )
        );
    }

    public function testReserveExcedPourcent()
    {
        $set = $this->createMock(ResourceSet::class);
        $set->expects(self::never())
            ->method('add');

        $this->expectException(ResourceCapacityExceededException::class);
        $this->createAvailability($this->getDefaultCapacity(), false)
            ->reserve(
                require: '5%',
                limit: '50%',
                numberOfReplicas: 10,
                set: $set,
            );
    }

    public function testReserveExcedPourcentWithSoftQuota()
    {
        $set = $this->createMock(ResourceSet::class);
        $set->expects(self::never())
            ->method('add');

        $this->expectException(ResourceCapacityExceededException::class);
        $this->createAvailability($this->getDefaultCapacity(), true)
            ->reserve(
                require: '5%',
                limit: '50%',
                numberOfReplicas: 10,
                set: $set,
            );
    }

    public function testReserveExceptionPourcentBiggerThan100()
    {
        $set = $this->createMock(ResourceSet::class);
        $set->expects(self::never())
            ->method('add');

        $this->expectException(ResourceWrongConfigurationException::class);
        $this->createAvailability($this->getDefaultCapacity(), false)
            ->reserve(
                require: '5%',
                limit: '500%',
                numberOfReplicas: 1,
                set: $set,
            );
    }

    public function testReserveExced()
    {
        $set = $this->createMock(ResourceSet::class);
        $set->expects(self::never())
            ->method('add');

        $this->expectException(ResourceCapacityExceededException::class);
        $this->createAvailability($this->getDefaultCapacity(), false)
            ->reserve(
                require: $this->getReserveValueCapacity(),
                limit: $this->getReserveValueCapacity(),
                numberOfReplicas: 10,
                set: $set,
            );
    }

    public function testUpdateResource()
    {
        self::assertInstanceOf(
            AvailabilityInterface::class,
            $this->createAvailability($this->getDefaultCapacity(), false)
                ->updateResource(
                    $this->createMock(AutomaticResource::class),
                    100
                ),
        );
    }
}