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

namespace Teknoo\Tests\East\Paas\Compilation\CompiledDeployment;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Resource;
use Teknoo\East\Paas\Compilation\CompiledDeployment\ResourceSet;

use function iterator_to_array;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ResourceSet::class)]
class ResourceSetTest extends TestCase
{
    private function buildObject(): ResourceSet
    {
        return new ResourceSet(
            resources: [
                $this->createMock(Resource::class),
                $this->createMock(Resource::class),
                $this->createMock(Resource::class),
            ]
        );
    }

    public function testGetIterator()
    {
        self::assertEquals(
            [
                $this->createMock(Resource::class),
                $this->createMock(Resource::class),
                $this->createMock(Resource::class),
            ],
            iterator_to_array($this->buildObject()),
        );
    }

    public function testCount()
    {
        self::assertEquals(
            3,
            $this->buildObject()->count(),
        );
    }

    public function testAdd()
    {
        $object = $this->buildObject();
        $object->add($this->createMock(Resource::class));

        self::assertEquals(
            4,
            $object->count(),
        );
    }
}
