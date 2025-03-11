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

namespace Teknoo\Tests\East\Paas\Compilation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment;
use Teknoo\East\Paas\Compilation\CompiledDeploymentFactory;
use Teknoo\East\Paas\Compilation\Exception\UnsupportedVersion;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(CompiledDeploymentFactory::class)]
class CompiledDeploymentFactoryTest extends TestCase
{
    public function testInvalidClass()
    {
        $this->expectException(\RuntimeException::class);
        new CompiledDeploymentFactory('foo', ['v1' => 'bar']);
    }

    public function testBuild()
    {
        self::assertInstanceOf(
            CompiledDeployment::class,
            (new CompiledDeploymentFactory(CompiledDeployment::class, ['v1' => 'bar']))
                ->build(1, 'prefix', 'foo')
        );
    }

    public function testGetSchema()
    {
        self::assertEquals(
            'bar',
            (new CompiledDeploymentFactory(CompiledDeployment::class, ['v1' => 'bar']))->getSchema('v1')
        );
    }

    public function testGetSchemaInvalidVersion()
    {
        $this->expectException(UnsupportedVersion::class);
        (new CompiledDeploymentFactory(CompiledDeployment::class, ['v1' => 'bar']))->getSchema('v2');
    }
}