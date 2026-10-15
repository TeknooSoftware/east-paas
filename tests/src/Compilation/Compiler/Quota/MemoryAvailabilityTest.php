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
use Teknoo\East\Paas\Compilation\CompiledDeployment\AutomaticResource;
use Teknoo\East\Paas\Compilation\CompiledDeployment\ResourceSet;
use Teknoo\East\Paas\Compilation\Compiler\Quota\AbstractAvailability;
use Teknoo\East\Paas\Compilation\Compiler\Quota\MemoryAvailability;
use Teknoo\East\Paas\Contracts\Compilation\Quota\AvailabilityInterface;

use function array_map;
use function intdiv;
use function iterator_to_array;
use function preg_replace;

use const PHP_INT_MAX;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(AbstractAvailability::class)]
#[CoversClass(MemoryAvailability::class)]
class MemoryAvailabilityTest extends AbstractTestAvailability
{
    protected function createAvailability(string $capacity, string $require, bool $isSoft): AvailabilityInterface
    {
        return new MemoryAvailability('test', $capacity, $require, $isSoft);
    }

    protected function getDefaultCapacity(): string
    {
        return '64Mi';
    }

    protected function getMiddleCapacity(): string
    {
        return '32Mi';
    }

    protected function getQuarterRequires(): string
    {
        return '8Mi';
    }

    protected function getLargerCapacity(): string
    {
        return '128Mi';
    }

    protected function getSmallerCapacity(): string
    {
        return '32Mi';
    }

    protected function getReserveValueCapacity(): string
    {
        return '10000Ki';
    }

    public static function requiresProvider(): array
    {
        return [
            ['10Ki', '4Ki'],
            ['10Mi', '4Mi'],
            ['10Gi', '4Gi'],
            ['10Ti', '4Ti'],
            ['10Pi', '4Pi'],
        ];
    }

    #[DataProvider('requiresProvider')]
    public function testOtherUpdates(string $capacity, string $result): void
    {
        $this->assertEquals($result, $this->createAvailability($capacity, $capacity, false)->update(
            $this->createAvailability('40%', '20%', true),
        )->getCapacity());
    }

    /**
     * Availability exposing the conversions between a quantity and its value in bytes
     */
    private function createConverter(): MemoryAvailability
    {
        return new class ('test', '0', '', false) extends MemoryAvailability {
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
     * The reading rounds up: the written capacity is also read in thousandths of byte, to check it is not lower
     * than the value
     */
    private function assertReadExactly(MemoryAvailability $converter, int $value, string $capacity): void
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
            'bytes' => ['512', 512],
            'bytes with decimals, rounded up' => ['1.5', 2],
            'half of a byte, rounded up' => ['0.5', 1],
            'K' => ['1.5K', 1500],
            'K, rounded up' => ['1.0005K', 1001],
            'M' => ['2.5M', 2500000],
            'G' => ['1.25G', 1250000000],
            'T' => ['1.5T', 1500000000000],
            'P' => ['0.5P', 500000000000000],
            'E' => ['1.5E', 1500000000000000000],
            'Ki' => ['1.5Ki', 1536],
            'Ki, rounded up' => ['0.1Ki', 103],
            'Mi' => ['1.5Mi', 1572864],
            'Mi, rounded up' => ['1.001Mi', 1049625],
            'Gi' => ['1.5Gi', 1610612736],
            'Gi written by East PaaS' => ['1.500Gi', 1610612736],
            'Gi lower than one' => ['0.5Gi', 536870912],
            'Gi without integer part' => ['.5Gi', 536870912],
            'Gi with 3 decimals' => ['1.172Gi', 1258425418],
            'Ti' => ['1.5Ti', 1649267441664],
            'Pi' => ['1.5Pi', 1688849860263936],
            'Ei' => ['1.5Ei', 1729382256910270464],
            'Ei with 3 decimals' => ['1.999Ei', 2304690087709087106],
            'Ei near the max integer' => ['7.999999999999999999Ei', PHP_INT_MAX],
            'tiny Gi, rounded up' => ['0.000000000000000001Gi', 1],
            'integer Mi' => ['64Mi', 67108864],
            'integer Gi with a space' => ['1 Gi', 1073741824],
            'leading space' => [' 1Gi', 1073741824],
            'positive sign' => ['+2Gi', 2147483648],
            'negative' => ['-2Gi', -2147483648],
            'exponent' => ['1e3', 1000],
            'exponent with decimals' => ['1.5e3', 1500],
            'exponent moving a part of the decimals' => ['1.2345e2', 124],
            'negative exponent, rounded up' => ['12e-1', 2],
            'negative exponent longer than the integer' => ['5e-3', 1],
            'empty' => ['', 0],
            'not a number' => ['foo', 0],
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
            'zero' => [0, '0'],
            'bytes' => [1000, '1000'],
            'whole Ki' => [10240, '10Ki'],
            'not a whole Ki' => [1500, '1500'],
            'whole Mi' => [67108864, '64Mi'],
            'Mi exact with 3 decimals' => [9961472, '9.500Mi'],
            'Mi not exact with 3 decimals, whole Ki' => [10240000, '10000Ki'],
            'Mi not exact with 3 decimals, bytes' => [10066329, '10066329'],
            'Mi lower than the value with 3 decimals, bytes' => [214748365, '214748365'],
            'whole Gi' => [2147483648, '2Gi'],
            'Gi exact with 3 decimals' => [1610612736, '1.500Gi'],
            'Gi not exact with 3 decimals, whole Mi' => [1258291200, '1200Mi'],
            'Gi not exact with 3 decimals, bytes' => [1073741825, '1073741825'],
            'Ti not exact with 3 decimals, whole Gi' => [1100585369600, '1025Gi'],
            'Pi exact with 3 decimals' => [1688849860263936, '1.500Pi'],
            'whole Ei' => [2305843009213693952, '2Ei'],
            'Ei not whole, whole Pi' => [1729382256910270464, '1536Pi'],
            'negative' => [-5, '-5'],
        ];
    }

    #[DataProvider('formatsProvider')]
    public function testValueIsWrittenWithoutLoss(int $value, string $expected): void
    {
        $converter = $this->createConverter();
        $this->assertSame($expected, $converter->format($value));
        $this->assertReadExactly($converter, $value, $expected);
    }

    public static function roundTripProvider(): array
    {
        $values = [
            'existing tests' => [
                64 * 1024 ** 2,
                32 * 1024 ** 2,
                16 * 1024 ** 2,
                128 * 1024 ** 2,
                10000 * 1024,
                64 * 1024 ** 2 - 10000 * 1024,
                4 * 1024,
                4 * 1024 ** 2,
                4 * 1024 ** 3,
                4 * 1024 ** 4,
                4 * 1024 ** 5,
                10 * 1024 ** 5,
            ],
            'automatic reservations' => [
                21474836,
                171798691,
                10066329,
                80 * 1024 ** 2,
            ],
            'relative values' => [
                322122547,
                107374182,
                214748365,
                1258291200,
            ],
        ];

        for ($power = 1; $power <= 6; ++$power) {
            $values['around 1024^' . $power] = [
                1024 ** $power - 1,
                1024 ** $power,
                1024 ** $power + 1,
                1024 ** $power + 1024 ** ($power - 1),
                3 * 1024 ** $power / 2,
            ];
        }

        $values['around 1024^6'][] = PHP_INT_MAX;

        return array_map(fn (array $list): array => [$list], $values);
    }

    #[DataProvider('roundTripProvider')]
    public function testRoundTrip(array $values): void
    {
        $converter = $this->createConverter();
        foreach ($values as $value) {
            $this->assertReadExactly($converter, $value, $converter->format($value));
        }
    }

    public function testRoundTripOfPercentages(): void
    {
        $converter = $this->createConverter();
        foreach ([64 * 1024 ** 2, 512 * 1024 ** 2, 1024 ** 3, 3 * 1024 ** 3, 10 * 1024 ** 3] as $capacity) {
            for ($percent = 1; $percent <= 100; ++$percent) {
                $value = (int) ($capacity * $percent / 100);
                $this->assertReadExactly($converter, $value, $converter->format($value));
            }
        }
    }

    public function testConstructionWithDecimalCapacity(): void
    {
        $availability = $this->createAvailability('1.5Gi', '1536Mi', false);

        $this->assertEquals('1.5Gi', $availability->getCapacity());
        $this->assertEquals('1536Mi', $availability->getRequires());
    }

    public function testReserveWithDecimalCapacity(): void
    {
        $availability = $this->createAvailability('1.5Gi', '1.5Gi', false);
        $availability->reserve(
            require: '1536Mi',
            limit: '1536Mi',
            numberOfReplicas: 1,
            set: new ResourceSet(),
        );

        $this->assertEquals('0', $availability->getCapacity());
        $this->assertEquals('0', $availability->getRequires());
    }

    public function testReserveWritesExactQuantities(): void
    {
        $set = new ResourceSet();
        $availability = $this->createAvailability('2Gi', '2Gi', false);
        $availability->reserve(
            require: '1200Mi',
            limit: '1200Mi',
            numberOfReplicas: 1,
            set: $set,
        );

        $this->assertEquals('848Mi', $availability->getCapacity());

        $availability->reserve(
            require: '848Mi',
            limit: '848Mi',
            numberOfReplicas: 1,
            set: $set,
        );

        $this->assertEquals('0', $availability->getCapacity());

        $resources = iterator_to_array($set);
        $this->assertCount(2, $resources);
        $this->assertSame('1200Mi', $resources[0]->getRequire());
        $this->assertSame('1200Mi', $resources[0]->getLimit());
        $this->assertSame('848Mi', $resources[1]->getRequire());
        $this->assertSame('848Mi', $resources[1]->getLimit());
    }

    public function testUpdateFromRelativeWithDecimals(): void
    {
        $final = $this->createAvailability('10Gi', '10Gi', false)->update(
            $this->createAvailability('15%', '15%', true),
        );

        $this->assertEquals('1.500Gi', $final->getCapacity());
        $this->assertEquals('1.500Gi', $final->getRequires());

        $final->reserve(
            require: '1536Mi',
            limit: '1536Mi',
            numberOfReplicas: 1,
            set: new ResourceSet(),
        );

        $this->assertEquals('0', $final->getCapacity());
        $this->assertEquals('0', $final->getRequires());
    }

    public function testUpdateFromRelativeNotExactWithThreeDecimals(): void
    {
        $final = $this->createAvailability('1Gi', '1Gi', false)->update(
            $this->createAvailability('30%', '10%', true),
        );

        $this->assertEquals('322122547', $final->getCapacity());
        $this->assertEquals('107374182', $final->getRequires());

        $final->reserve(
            require: '107374182',
            limit: '107374182',
            numberOfReplicas: 1,
            set: new ResourceSet(),
        );

        $this->assertEquals('214748365', $final->getCapacity());
        $this->assertEquals('0', $final->getRequires());
    }

    public function testUpdateResourceWritesExactQuantities(): void
    {
        $availability = $this->createAvailability('1Gi', '1Gi', false);

        $availability->updateResource($sixteen = new AutomaticResource('memory'), 16);
        $this->assertSame('21474836', $sixteen->getRequire());
        $this->assertSame('171798691', $sixteen->getLimit());

        $availability->updateResource($half = new AutomaticResource('memory'), 50);
        $this->assertSame('53687091', $half->getRequire());
        $this->assertSame('512Mi', $half->getLimit());
    }

    public static function relativeRequiresProvider(): array
    {
        return [
            'parent with requires' => ['4Gi', '2Gi', '50%', '50%', '2Gi', '1Gi'],
            'parent without requires' => ['4Gi', '', '50%', '50%', '2Gi', '2Gi'],
            'requires defaulted to the relative capacity' => ['4Gi', '2Gi', '50%', '', '2Gi', '1Gi'],
            'absolute capacity' => ['4Gi', '2Gi', '2Gi', '50%', '2Gi', '1Gi'],
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
            'parent with requires' => ['4Gi', '2Gi', '50%', '50%', '1Gi', '512Mi'],
            'parent without requires, first entry with a relative requires' => [
                '4Gi',
                '',
                '50%',
                '25%',
                '1Gi',
                '512Mi',
            ],
            'parent without requires, first entry with an absolute requires' => [
                '4Gi',
                '',
                '50%',
                '1Gi',
                '1Gi',
                '512Mi',
            ],
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
        $availability = $this->createAvailability('64Mi', '16Mi', false);

        $this->assertSame(
            $availability,
            $availability->reserve(
                require: '4Mi',
                limit: '32Mi',
                numberOfReplicas: 1,
                set: $set,
            ),
        );

        $this->assertCount(1, $set);
        $resource = iterator_to_array($set)[0];
        $this->assertEquals('4Mi', $resource->getRequire());
        $this->assertEquals('32Mi', $resource->getLimit());

        $this->assertEquals('12Mi', $availability->getRequires());
        $this->assertEquals('32Mi', $availability->getCapacity());
    }

    public function testReserveRequireLowerThanRemainingRequiresWithReplicas(): void
    {
        $set = new ResourceSet();
        $availability = $this->createAvailability('64Mi', '16Mi', false);

        $this->assertSame(
            $availability,
            $availability->reserve(
                require: '4Mi',
                limit: '32Mi',
                numberOfReplicas: 2,
                set: $set,
            ),
        );

        $this->assertCount(1, $set);
        $this->assertEquals('8Mi', $availability->getRequires());
        $this->assertEquals('0', $availability->getCapacity());
    }
}
