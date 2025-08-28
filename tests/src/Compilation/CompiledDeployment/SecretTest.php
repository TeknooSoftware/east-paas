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
use Teknoo\East\Paas\Compilation\CompiledDeployment\Secret;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Secret::class)]
class SecretTest extends TestCase
{
    private function buildObject(): Secret
    {
        return new Secret('foo', 'bar', ['foo' => 'bar']);
    }

    public function testGetName(): void
    {
        $this->assertEquals('foo', $this->buildObject()->getName());
    }

    public function testGetProvider(): void
    {
        $this->assertEquals('bar', $this->buildObject()->getProvider());
    }

    public function testGetOptions(): void
    {
        $this->assertEquals(['foo' => 'bar'], $this->buildObject()->getOptions());
    }

    public function testGetType(): void
    {
        $this->assertEquals('default', $this->buildObject()->getType());

        $this->assertEquals('tls', new Secret('foo', 'bar', ['foo' => 'bar'], 'tls')->getType());
    }
}
