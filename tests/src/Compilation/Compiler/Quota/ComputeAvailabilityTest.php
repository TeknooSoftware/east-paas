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

use Teknoo\East\Paas\Compilation\Compiler\Quota\ComputeAvailability;
use Teknoo\East\Paas\Contracts\Compilation\Quota\AvailabilityInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Compilation\Compiler\Quota\ComputeAvailability
 * @covers \Teknoo\East\Paas\Compilation\Compiler\Quota\AbstractAvailability
 */
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