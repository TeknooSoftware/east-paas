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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Compilation\CompiledDeployment\Expose;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Ingress;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\IngressPath;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Ingress
 */
class IngressTest extends TestCase
{
    private function buildObject($internal = false): Ingress
    {
        return new Ingress(
            'foo',
            'bar.com',
            'providerName',
            'bar',
            8080,
            [
                new IngressPath('/foo', 'bar', 80)
            ],
            'fooSecret',
            true
        );
    }

    public function testGetName()
    {
        self::assertEquals(
            'foo',
            $this->buildObject()->getName()
        );
    }

    public function testGetHost()
    {
        self::assertEquals(
            'bar.com',
            $this->buildObject()->getHost()
        );
    }

    public function testGetProvider()
    {
        self::assertEquals(
            'providerName',
            $this->buildObject()->getProvider()
        );
    }

    public function testGetPorts()
    {
        self::assertEquals(
            'bar',
            $this->buildObject()->getDefaultServiceName()
        );
    }

    public function testGetProtocol()
    {
        self::assertEquals(
            8080,
            $this->buildObject()->getDefaultServicePort()
        );
    }

    public function testIsInternal()
    {
        self::assertEquals(
            [
                new IngressPath('/foo', 'bar', 80)
            ],
            $this->buildObject()->getPaths()
        );
    }

    public function testGetTlsSecret()
    {
        self::assertEquals(
            'fooSecret',
            $this->buildObject()->getTlsSecret()
        );
    }

    public function testIsHttpsBackend()
    {
        self::assertTrue($this->buildObject()->isHttpsBackend());

        self::assertFalse(
            (new Ingress(
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
            ))->isHttpsBackend()
        );
    }
}