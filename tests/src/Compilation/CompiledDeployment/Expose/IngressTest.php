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
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Ingress;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\IngressPath;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Ingress::class)]
class IngressTest extends TestCase
{
    private function buildObject(): Ingress
    {
        return new Ingress(
            name: 'foo',
            host: 'bar.com',
            provider: 'providerName',
            defaultServiceName: 'bar',
            defaultServicePort: 8080,
            paths: [
                new IngressPath('/foo', 'bar', 80)
            ],
            tlsSecret: 'fooSecret',
            httpsBackend: true,
            meta: ['foo' => 'bar'],
            aliases: [
                'www.bar.com',
                'www2.bar.com',
            ]
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals('foo', $this->buildObject()->getName());
    }

    public function testGetHost(): void
    {
        $this->assertEquals('bar.com', $this->buildObject()->getHost());
    }

    public function testGetProvider(): void
    {
        $this->assertEquals('providerName', $this->buildObject()->getProvider());
    }

    public function testGetPorts(): void
    {
        $this->assertEquals('bar', $this->buildObject()->getDefaultServiceName());
    }

    public function testGetProtocol(): void
    {
        $this->assertEquals(8080, $this->buildObject()->getDefaultServicePort());
    }

    public function testIsInternal(): void
    {
        $this->assertEquals([
            new IngressPath('/foo', 'bar', 80)
        ], $this->buildObject()->getPaths());
    }

    public function testGetTlsSecret(): void
    {
        $this->assertEquals('fooSecret', $this->buildObject()->getTlsSecret());
    }

    public function testIsHttpsBackend(): void
    {
        $this->assertTrue($this->buildObject()->isHttpsBackend());

        $this->assertFalse(new Ingress(
            'foo',
            'bar.com',
            'providerName',
            'bar',
            8080,
            [
                new IngressPath('/foo', 'bar', 80)
            ],
            'fooSecret',
            false,
        )->isHttpsBackend());
    }

    public function testGetMeta(): void
    {
        $this->assertEquals(['foo' => 'bar'], $this->buildObject()->getMeta());
    }

    public function testGetAliases(): void
    {
        $this->assertEquals([
            'www.bar.com',
            'www2.bar.com',
        ], $this->buildObject()->getAliases());
    }
}
