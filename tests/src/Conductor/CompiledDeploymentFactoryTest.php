<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
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

namespace Teknoo\Tests\East\Paas\Conductor;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Conductor\CompiledDeploymentFactory;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Conductor\CompiledDeploymentFactory
 */
class CompiledDeploymentFactoryTest extends TestCase
{
    public function testInvalidClass()
    {
        $this->expectException(\RuntimeException::class);
        new CompiledDeploymentFactory('foo', 'bar');
    }

    public function testBuild()
    {
        self::assertInstanceOf(
            CompiledDeployment::class,
            (new CompiledDeploymentFactory(CompiledDeployment::class, 'bar'))->build(1, 'bar')
        );
    }

    public function testGetSchema()
    {
        self::assertEquals(
            'bar',
            (new CompiledDeploymentFactory(CompiledDeployment::class, 'bar'))->getSchema()
        );
    }
}