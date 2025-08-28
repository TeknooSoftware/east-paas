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

namespace Teknoo\Tests\East\Paas\Compilation\CompiledDeployment\Expose;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\IngressPath;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(IngressPath::class)]
class IngressPathTest extends TestCase
{
    private function buildObject(): IngressPath
    {
        return new IngressPath('/foo', 'bar', 80);
    }

    public function testGetPath(): void
    {
        $this->assertEquals('/foo', $this->buildObject()->getPath());
    }

    public function testGetPodName(): void
    {
        $this->assertEquals('bar', $this->buildObject()->getServiceName());
    }

    public function testGetPorts(): void
    {
        $this->assertEquals(80, $this->buildObject()->getServicePort());
    }
}
