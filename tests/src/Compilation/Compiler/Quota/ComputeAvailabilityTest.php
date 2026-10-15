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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Teknoo\East\Paas\Compilation\CompiledDeployment\ResourceSet;
use Teknoo\East\Paas\Compilation\Compiler\Exception\ResourceCapacityExceededException;
use Teknoo\East\Paas\Compilation\Compiler\Quota\AbstractAvailability;
use Teknoo\East\Paas\Compilation\Compiler\Quota\ComputeAvailability;
use Teknoo\East\Paas\Contracts\Compilation\Quota\AvailabilityInterface;

use function intdiv;
use function iterator_to_array;
use function preg_replace;

use const PHP_INT_MAX;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(AbstractAvailability::class)]
#[CoversClass(ComputeAvailability::class)]
class ComputeAvailabilityTest extends AbstractTestAvailability
{
    protected function createAvailability(string $capacity, string $require, bool $isSoft): AvailabilityInterface
    {
        return new ComputeAvailability('test', $capacity, $require, $isSoft);
    }

    protected function getDefaultCapacity(): string
    {
        return '2';
    }

    protected function getMiddleCapacity(): string
    {
        return '1.000';
    }

    protected function getQuarterRequires(): string
    {
        return '250m';
    }

    protected function getLargerCapacity(): string
    {
        return '3';
    }

    protected function getSmallerCapacity(): string
    {
        return '1';
    }

    protected function getReserveValueCapacity(): string
    {
        return '500m';
    }

    /**
     * Availability exposing the conversions between a quantity and its value in millicores
     */
    private function createConverter(): ComputeAvailability
    {
        return new class ('test', '0', '', false) extends ComputeAvailability {
            public function parse(string $capacity): int
            {
                return $this->stringCapacityToValue($capacity);
            }

            public function format(int $value): string
            {
                return $this->valueToStringCapacity($value);
            }
        };
    }

    /**
     * The reading rounds up: the written capacity is also read in thousandths of millicore, to check it is not lower
     * than the value
     */
    private function assertReadExactly(ComputeAvailability $converter, int $value, string $capacity): void
    {
        $this->assertSame($value, $converter->parse($capacity), "{$capacity} for {$value}");

        if ($value <= intdiv(PHP_INT_MAX, 1000)) {
            $this->assertSame(
                $value * 1000,
                $converter->parse((string) preg_replace('/^([+-]?[\d.]+)/', '${1}e3', $capacity)),
                "{$capacity} for {$value}, in thousandths",
            );
        }
    }

    public static function quantitiesProvider(): array
    {
        return [
            'cores' => ['2', 2000],
            'millicores' => ['500m', 500],
            'cores with decimals' => ['1.5', 1500],
            'cores written by East PaaS' => ['1.600', 1600],
            'cores without integer part' => ['.5', 500],
            '1.001 truncated by a float' => ['1.001', 1001],
            '1.003 truncated by a float' => ['1.003', 1003],
            '2.002 truncated by a float' => ['2.002', 2002],
            '2.030 truncated by a float' => ['2.030', 2030],
            '4.004 truncated by a float' => ['4.004', 4004],
            '2.3' => ['2.3', 2300],
            '4.35' => ['4.35', 4350],
            '0.007' => ['0.007', 7],
            'cores rounded up to the millicore' => ['1.0001', 1001],
            'tiny cores rounded up to the millicore' => ['0.0001', 1],
            'millicores with decimals, rounded up' => ['0.5m', 1],
            'millicores with decimals, rounded up again' => ['1.5m', 2],
            'exponent' => ['1e1', 10000],
            'empty' => ['', 0],
        ];
    }

    #[DataProvider('quantitiesProvider')]
    public function testQuantityIsReadLikeKubernetes(string $quantity, int $expected): void
    {
        $this->assertSame($expected, $this->createConverter()->parse($quantity));
    }

    public static function formatsProvider(): array
    {
        return [
            'zero' => [0, '0m'],
            'millicores' => [500, '500m'],
            'one core' => [1000, '1.000'],
            'cores and millicores' => [1001, '1.001'],
            'automatic reservation' => [1600, '1.600'],
            'cores' => [2000, '2.000'],
        ];
    }

    #[DataProvider('formatsProvider')]
    public function testValueIsWrittenWithoutLoss(int $value, string $expected): void
    {
        $converter = $this->createConverter();
        $this->assertSame($expected, $converter->format($value));
        $this->assertReadExactly($converter, $value, $expected);
    }

    public function testRoundTrip(): void
    {
        $converter = $this->createConverter();
        for ($value = 0; $value <= 20000; ++$value) {
            $this->assertReadExactly($converter, $value, $converter->format($value));
        }
    }

    public function testConstructionWithDecimalCapacity(): void
    {
        $availability = $this->createAvailability('1.001', '1001m', false);

        $this->assertEquals('1.001', $availability->getCapacity());
        $this->assertEquals('1001m', $availability->getRequires());
    }

    public function testReserveWithDecimalCapacity(): void
    {
        $availability = $this->createAvailability('1.001', '1.001', false);
        $availability->reserve(
            require: '1001m',
            limit: '1001m',
            numberOfReplicas: 1,
            set: new ResourceSet(),
        );

        $this->assertEquals('0m', $availability->getCapacity());
        $this->assertEquals('0m', $availability->getRequires());
    }

    public static function relativeRequiresProvider(): array
    {
        return [
            'parent with requires' => ['4', '2', '50%', '50%', '2.000', '1.000'],
            'parent without requires' => ['4', '', '50%', '50%', '2.000', '2.000'],
            'requires defaulted to the relative capacity' => ['4', '2', '50%', '', '2.000', '1.000'],
            'absolute capacity' => ['4', '2', '2', '50%', '2', '1.000'],
        ];
    }

    #[DataProvider('relativeRequiresProvider')]
    public function testUpdateWithRelativeRequires(
        string $parentCapacity,
        string $parentRequires,
        string $capacity,
        string $requires,
        string $expectedCapacity,
        string $expectedRequires,
    ): void {
        $final = $this->createAvailability($parentCapacity, $parentRequires, false)->update(
            $this->createAvailability($capacity, $requires, true),
        );

        $this->assertEquals($expectedCapacity, $final->getCapacity());
        $this->assertEquals($expectedRequires, $final->getRequires());
    }

    public static function relativeRequiresTwiceProvider(): array
    {
        return [
            'parent with requires' => ['4', '2', '50%', '50%', '1.000', '500m'],
            'parent without requires, first entry with a relative requires' => ['4', '', '50%', '25%', '1.000', '500m'],
            'parent without requires, first entry with an absolute requires' => ['4', '', '50%', '1', '1.000', '500m'],
        ];
    }

    #[DataProvider('relativeRequiresTwiceProvider')]
    public function testUpdateTwiceWithRelativeRequires(
        string $parentCapacity,
        string $parentRequires,
        string $firstCapacity,
        string $firstRequires,
        string $expectedCapacity,
        string $expectedRequires,
    ): void {
        $final = $this->createAvailability($parentCapacity, $parentRequires, false)
            ->update($this->createAvailability($firstCapacity, $firstRequires, true))
            ->update($this->createAvailability('50%', '50%', true));

        $this->assertEquals($expectedCapacity, $final->getCapacity());
        $this->assertEquals($expectedRequires, $final->getRequires());
    }

    public function testReserveRequireLowerThanRemainingRequiresAndLimitBiggerThanRemainingRequires(): void
    {
        $set = new ResourceSet();
        $availability = $this->createAvailability('2', '500m', false);

        $this->assertSame(
            $availability,
            $availability->reserve(
                require: '100m',
                limit: '1',
                numberOfReplicas: 1,
                set: $set,
            ),
        );

        $this->assertCount(1, $set);
        $resource = iterator_to_array($set)[0];
        $this->assertEquals('100m', $resource->getRequire());
        $this->assertEquals('1.000', $resource->getLimit());

        $this->assertEquals('400m', $availability->getRequires());
        $this->assertEquals('1.000', $availability->getCapacity());
    }

    public function testReserveRequireLowerThanRemainingRequiresWithReplicas(): void
    {
        $set = new ResourceSet();
        $availability = $this->createAvailability('4', '500m', false);

        $this->assertSame(
            $availability,
            $availability->reserve(
                require: '100m',
                limit: '1',
                numberOfReplicas: 3,
                set: $set,
            ),
        );

        $this->assertCount(1, $set);
        $this->assertEquals('200m', $availability->getRequires());
        $this->assertEquals('1.000', $availability->getCapacity());
    }

    public function testReserveRequireBiggerThanRemainingRequires(): void
    {
        $set = new ResourceSet();

        $this->expectException(ResourceCapacityExceededException::class);
        $this->expectExceptionMessage(
            'Error, remaining available capacity to require for `test` is `500m`, but current requires `600m`'
        );

        try {
            $this->createAvailability('2', '500m', false)->reserve(
                require: '600m',
                limit: '1',
                numberOfReplicas: 1,
                set: $set,
            );
        } finally {
            $this->assertCount(0, $set);
        }
    }

    public function testReserveRequireBiggerThanRemainingRequiresWithReplicas(): void
    {
        $set = new ResourceSet();

        $this->expectException(ResourceCapacityExceededException::class);
        $this->expectExceptionMessage(
            'Error, remaining available capacity to require for `test` is `500m`, but current requires `200m`'
        );

        try {
            $this->createAvailability('4', '500m', false)->reserve(
                require: '200m',
                limit: '1',
                numberOfReplicas: 3,
                set: $set,
            );
        } finally {
            $this->assertCount(0, $set);
        }
    }
}
