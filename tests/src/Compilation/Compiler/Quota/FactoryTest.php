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
use PHPUnit\Framework\TestCase;
use stdClass;
use Teknoo\East\Paas\Compilation\Compiler\Exception\QuotaWrongConfigurationException;
use Teknoo\East\Paas\Compilation\Compiler\Quota\ComputeAvailability;
use Teknoo\East\Paas\Compilation\Compiler\Quota\Factory;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Factory::class)]
class FactoryTest extends TestCase
{
    private function createObject(): Factory
    {
        return new Factory(
            [
                'compute' => ComputeAvailability::class,
                'wrong' => stdClass::class,
            ]
        );
    }

    public function testCreate(): void
    {
        $this->assertInstanceOf(ComputeAvailability::class, $availability = $this->createObject()->create('compute', 'cpu', '5', '3', false));

        $this->assertEquals('5', $availability->getCapacity());
    }

    public function testCreateExceptionCategoryNotDefined(): void
    {
        $this->expectException(QuotaWrongConfigurationException::class);
        $this->createObject()->create('foo', 'cpu', '5', '3', false);
    }

    public function testCreateExceptionCategoryWrongClass(): void
    {
        $this->expectException(QuotaWrongConfigurationException::class);
        $this->createObject()->create('wrong', 'cpu', '5', '3', false);
    }
}
