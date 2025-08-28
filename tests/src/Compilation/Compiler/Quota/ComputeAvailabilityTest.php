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
use Teknoo\East\Paas\Compilation\Compiler\Quota\AbstractAvailability;
use Teknoo\East\Paas\Compilation\Compiler\Quota\ComputeAvailability;
use Teknoo\East\Paas\Contracts\Compilation\Quota\AvailabilityInterface;

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

    protected function getQuarterCapacity(): string
    {
        return '500m';
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
}
