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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Compilation\Compiler\Quota;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Teknoo\East\Paas\Compilation\Compiler\Quota\AbstractAvailability;
use Teknoo\East\Paas\Compilation\Compiler\Quota\MemoryAvailability;
use Teknoo\East\Paas\Contracts\Compilation\Quota\AvailabilityInterface;

/**
 * @license     https://teknoo.software/license/mit         MIT License
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

    protected function getQuarterCapacity(): string
    {
        return '16Mi';
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
    public function testOtherUpdates(string $capacity, string $result)
    {
        self::assertEquals(
            $result,
            $this->createAvailability($capacity, $capacity, false)->update(
                $this->createAvailability('40%', '20%', true),
            )->getCapacity()
        );
    }
}