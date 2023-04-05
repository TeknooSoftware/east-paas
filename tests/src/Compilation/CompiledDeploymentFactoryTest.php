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
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Compilation;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment;
use Teknoo\East\Paas\Compilation\CompiledDeploymentFactory;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Compilation\CompiledDeploymentFactory
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
            (new CompiledDeploymentFactory(CompiledDeployment::class, 'bar'))
                ->build(1, 'bar', true, 'prefix')
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