<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Compilation\CompiledDeployment\Expose;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Service;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Service
 */
class ServiceTest extends TestCase
{
    private function buildObject($internal = false): Service
    {
        return new Service('foo', 'bar', [80 => 8080], 'TCP', $internal);
    }

    public function testGetName()
    {
        self::assertEquals(
            'foo',
            $this->buildObject()->getName()
        );
    }

    public function testGetPodName()
    {
        self::assertEquals(
            'bar',
            $this->buildObject()->getPodName()
        );
    }

    public function testGetPorts()
    {
        self::assertEquals(
            [80 => 8080],
            $this->buildObject()->getPorts()
        );
    }

    public function testGetProtocol()
    {
        self::assertEquals(
            'TCP',
            $this->buildObject()->getProtocol()
        );
    }

    public function testIsInternal()
    {
        self::assertFalse(
            $this->buildObject()->isInternal()
        );

        self::assertTrue(
            $this->buildObject(true)->isInternal()
        );
    }
}