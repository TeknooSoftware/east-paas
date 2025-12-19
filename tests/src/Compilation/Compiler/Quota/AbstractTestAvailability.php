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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
abstract class AbstractTestAvailability extends TestCase
{
    abstract protected function createAvailability(
        string $capacity,
        string $require,
        bool $isSoft,
    ): AvailabilityInterface;

    abstract protected function getDefaultCapacity(): string;

    abstract protected function getMiddleCapacity(): string;

    abstract protected function getQuarterCapacity(): string;

    abstract protected function getLargerCapacity(): string;

    abstract protected function getSmallerCapacity(): string;

    abstract protected function getReserveValueCapacity(): string;

    public function testConstructionErrorIfCapacityIsRelativeForNonSoft(): void
    {
        $this->expectException(QuotaWrongConfigurationException::class);
        $this->createAvailability('100%', '10', false);
    }

    public function testConstructionNoErrorIfCapacityIsRelativeForNonSoft(): void
    {
        $this->assertInstanceOf(AvailabilityInterface::class, $this->createAvailability('100%', '10', true));
    }

    public function testConstructionErrorIfRequiresCapacityIsRelativeForNonSoft(): void
    {
        $this->expectException(QuotaWrongConfigurationException::class);
        $this->createAvailability('100', '10%', false);
    }

    public function testConstructionNoErrorIfRequiresCapacityIsRelativeForNonSoft(): void
    {
        $this->assertInstanceOf(AvailabilityInterface::class, $this->createAvailability('100', '10%', true));
    }

    public function testConstructionErrorIfRequiresCapacityIsBiggerThanCapaciy(): void
    {
        $this->expectException(QuotaWrongConfigurationException::class);
        $this->createAvailability($this->getSmallerCapacity(), $this->getDefaultCapacity(), false);
    }

    public function testGetCapacity(): void
    {
        $this->assertEquals($this->getDefaultCapacity(), $this->createAvailability($this->getDefaultCapacity(), $this->getDefaultCapacity(), false)->getCapacity());
    }

    public function testGetRequire(): void
    {
        $this->assertEquals($this->getSmallerCapacity(), $this->createAvailability($this->getDefaultCapacity(), $this->getSmallerCapacity(), false)->getRequires());

        $this->assertEquals($this->getDefaultCapacity(), $this->createAvailability($this->getDefaultCapacity(), '', false)->getRequires());
    }

    public function testUpdateWrongClass(): void
    {
        $this->expectException(QuotasNotCompliantException::class);
        $this->createAvailability($this->getDefaultCapacity(), $this->getDefaultCapacity(), false)->update(
            $this->createStub(AvailabilityInterface::class),
        );
    }

    public function testUpdateWithLargerCapacity(): void
    {
        $this->expectException(ResourceCapacityExceededException::class);
        $this->createAvailability($this->getDefaultCapacity(), $this->getDefaultCapacity(), false)->update(
            $this->createAvailability($this->getLargerCapacity(), $this->getDefaultCapacity(), false),
        );
    }

    public function testUpdate(): void
    {
        $this->assertEquals($this->getSmallerCapacity(), $this->createAvailability($this->getDefaultCapacity(), $this->getDefaultCapacity(), false)->update(
            $this->createAvailability($this->getSmallerCapacity(), $this->getSmallerCapacity(), false),
        )->getCapacity());
    }

    public function testUpdateFromRelative(): void
    {
        $this->assertInstanceOf(AvailabilityInterface::class, $hard = $this->createAvailability($this->getDefaultCapacity(), $this->getSmallerCapacity(), false));

        $this->assertInstanceOf(AvailabilityInterface::class, $soft = $this->createAvailability('50%', '25%', true));

        $final = $hard->update($soft);
        $this->assertInstanceOf(AvailabilityInterface::class, $final);

        $this->assertNotSame($final, $soft);

        $this->assertEquals($this->getMiddleCapacity(), $final->getCapacity());

        $this->assertEquals($this->getQuarterCapacity(), $final->getRequires());
    }

    public function testReserveLimitSmallerThanRequire(): void
    {
        $set = $this->createMock(ResourceSet::class);
        $set->expects($this->never())
            ->method('add');

        $this->expectException(ResourceWrongConfigurationException::class);
        $this->createAvailability($this->getDefaultCapacity(), $this->getDefaultCapacity(), false)->reserve(
            require: '500',
            limit: '100',
            numberOfReplicas: 1,
            set: $set,
        );
    }

    public function testReserveLimitBiggerThanCapacity(): void
    {
        $set = $this->createMock(ResourceSet::class);
        $set->expects($this->never())
            ->method('add');

        $this->expectException(ResourceCapacityExceededException::class);
        $this->createAvailability($this->getDefaultCapacity(), $this->getDefaultCapacity(), false)->reserve(
            require: $this->getLargerCapacity(),
            limit: $this->getLargerCapacity(),
            numberOfReplicas: 1,
            set: $set,
        );
    }

    public function testReserveLimitBiggerThanCapacityWithSoftQuota(): void
    {
        $set = $this->createMock(ResourceSet::class);
        $set->expects($this->never())
            ->method('add');

        $this->expectException(ResourceCapacityExceededException::class);
        $this->createAvailability($this->getDefaultCapacity(), $this->getDefaultCapacity(), true)->reserve(
            require: $this->getLargerCapacity(),
            limit: $this->getLargerCapacity(),
            numberOfReplicas: 1,
            set: $set,
        );
    }

    public function testReserve(): void
    {
        $set = $this->createMock(ResourceSet::class);
        $set->expects($this->once())
            ->method('add');

        $this->assertInstanceOf(AvailabilityInterface::class, $this->createAvailability($this->getDefaultCapacity(), $this->getDefaultCapacity(), false)
            ->reserve(
                require: $this->getReserveValueCapacity(),
                limit: $this->getReserveValueCapacity(),
                numberOfReplicas: 1,
                set: $set,
            ));
    }

    public function testReservePourcent(): void
    {
        $set = $this->createMock(ResourceSet::class);
        $set->expects($this->once())
            ->method('add');

        $this->assertInstanceOf(AvailabilityInterface::class, $this->createAvailability($this->getDefaultCapacity(), $this->getDefaultCapacity(), false)
            ->reserve(
                require: '5%',
                limit: '50%',
                numberOfReplicas: 1,
                set: $set,
            ));
    }

    public function testReserveExceedPourcent(): void
    {
        $set = $this->createMock(ResourceSet::class);
        $set->expects($this->never())
            ->method('add');

        $this->expectException(ResourceCapacityExceededException::class);
        $this->createAvailability($this->getDefaultCapacity(), $this->getDefaultCapacity(), false)
            ->reserve(
                require: '5%',
                limit: '50%',
                numberOfReplicas: 10,
                set: $set,
            );
    }

    public function testReserveExceedPourcentWithSoftQuota(): void
    {
        $set = $this->createMock(ResourceSet::class);
        $set->expects($this->never())
            ->method('add');

        $this->expectException(ResourceCapacityExceededException::class);
        $this->createAvailability($this->getDefaultCapacity(), $this->getDefaultCapacity(), true)
            ->reserve(
                require: '5%',
                limit: '50%',
                numberOfReplicas: 10,
                set: $set,
            );
    }

    public function testReserveExceptionPourcentBiggerThan100(): void
    {
        $set = $this->createMock(ResourceSet::class);
        $set->expects($this->never())
            ->method('add');

        $this->expectException(ResourceWrongConfigurationException::class);
        $this->createAvailability($this->getDefaultCapacity(), $this->getDefaultCapacity(), false)
            ->reserve(
                require: '5%',
                limit: '500%',
                numberOfReplicas: 1,
                set: $set,
            );
    }

    public function testReserveExceed(): void
    {
        $set = $this->createMock(ResourceSet::class);
        $set->expects($this->never())
            ->method('add');

        $this->expectException(ResourceCapacityExceededException::class);
        $this->createAvailability($this->getDefaultCapacity(), $this->getDefaultCapacity(), false)
            ->reserve(
                require: $this->getReserveValueCapacity(),
                limit: $this->getReserveValueCapacity(),
                numberOfReplicas: 10,
                set: $set,
            );
    }

    public function testReserveExceedForRequiresCapacity(): void
    {
        $set = $this->createMock(ResourceSet::class);
        $set->expects($this->never())
            ->method('add');

        $this->expectException(ResourceCapacityExceededException::class);
        $this->createAvailability($this->getLargerCapacity(), $this->getSmallerCapacity(), false)
            ->reserve(
                require: $this->getLargerCapacity(),
                limit: $this->getLargerCapacity(),
                numberOfReplicas: 1,
                set: $set,
            );
    }

    public function testReserveExceedForRequiresCapacityWithSoftQuota(): void
    {
        $set = $this->createMock(ResourceSet::class);
        $set->expects($this->never())
            ->method('add');

        $this->expectException(ResourceCapacityExceededException::class);
        $this->createAvailability($this->getLargerCapacity(), $this->getSmallerCapacity(), true)
            ->reserve(
                require: $this->getLargerCapacity(),
                limit: $this->getLargerCapacity(),
                numberOfReplicas: 1,
                set: $set,
            );
    }

    public function testUpdateResource(): void
    {
        $this->assertInstanceOf(AvailabilityInterface::class, $this->createAvailability($this->getDefaultCapacity(), $this->getDefaultCapacity(), false)
            ->updateResource(
                $this->createStub(AutomaticResource::class),
                100
            ));
    }
}
