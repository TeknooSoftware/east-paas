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

namespace Teknoo\Tests\East\Paas\Compilation\CompiledDeployment;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Resource;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Resource::class)]
class ResourceTest extends TestCase
{
    private function buildObject(): Resource
    {
        return new Resource(
            type: 'cpu',
            require: '100m',
            limit: '500m',
        );
    }

    public function testGetType(): void
    {
        $this->assertEquals('cpu', $this->buildObject()->getType());
    }

    public function testGetRequire(): void
    {
        $this->assertEquals('100m', $this->buildObject()->getRequire());
    }

    public function testGetLimit(): void
    {
        $this->assertEquals('500m', $this->buildObject()->getLimit());
    }
}
