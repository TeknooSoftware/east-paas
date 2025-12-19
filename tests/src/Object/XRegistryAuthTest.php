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

namespace Teknoo\Tests\East\Paas\Object;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Paas\Object\XRegistryAuth;
use Teknoo\Tests\East\Common\Object\Traits\ObjectTestTrait;
use TypeError;

/**
 * @author      Richard Déloge <richard@teknoo.software>
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(XRegistryAuth::class)]
class XRegistryAuthTest extends TestCase
{
    use ObjectTestTrait;

    public function buildObject(): XRegistryAuth
    {
        return new XRegistryAuth('fooName', 'fooBar', 'barFoo', 'fooBar', 'barFoo');
    }

    public function testGetName(): void
    {
        $this->assertEquals('fooName', $this->generateObjectPopulated()->getName());
    }

    public function testToString(): void
    {
        $this->assertEquals('fooName', (string) $this->generateObjectPopulated());
    }

    public function testGetUsername(): void
    {
        $this->assertEquals('fooName', $this->generateObjectPopulated()->getUsername());
    }

    public function testGetPassword(): void
    {
        $this->assertEquals('fooBar', $this->generateObjectPopulated()->getPassword());
    }

    public function testGetEmail(): void
    {
        $this->assertEquals('barFoo', $this->generateObjectPopulated()->getEmail());
    }

    public function testGetAuth(): void
    {
        $this->assertEquals('fooBar', $this->generateObjectPopulated()->getAuth());
    }

    public function testGetConfigName(): void
    {
        $this->assertEquals('fooBar', $this->generateObjectPopulated()->getConfigName());
    }

    public function testGetServerAddress(): void
    {
        $this->assertEquals('barFoo', $this->generateObjectPopulated()->getServerAddress());
    }

    public function testExportToMeDataBadNormalizer(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->exportToMeData(new stdClass(), []);
    }

    public function testExportToMeDataBadContext(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->exportToMeData(
            $this->createStub(EastNormalizerInterface::class),
            new stdClass()
        );
    }

    public function testExportToMe(): void
    {
        $normalizer = $this->createMock(EastNormalizerInterface::class);
        $normalizer->expects($this->once())
            ->method('injectData')
            ->with([
                '@class' => XRegistryAuth::class,
                'id' => '123',
                'username' => 'fooName',
                'password' => 'fooBar',
                'email' => 'barFoo',
                'auth' => 'fooBar',
                'server_address' => 'barFoo',
            ]);

        $this->assertInstanceOf(XRegistryAuth::class, $this->buildObject()->setId('123')->exportToMeData(
            $normalizer,
            ['foo' => 'bar']
        ));
    }

    public function testExportToMeApi(): void
    {
        $normalizer = $this->createMock(EastNormalizerInterface::class);
        $normalizer->expects($this->once())
            ->method('injectData')
            ->with([
                '@class' => XRegistryAuth::class,
                'id' => '123',
                'username' => 'fooName',
                'email' => 'barFoo',
                'server_address' => 'barFoo',
            ]);

        $this->assertInstanceOf(XRegistryAuth::class, $this->buildObject()->setId('123')->exportToMeData(
            $normalizer,
            ['groups' => 'api']
        ));
    }

    public function testSetExportConfiguration(): void
    {
        XRegistryAuth::setExportConfiguration($conf = ['name' => ['all']]);
        $rc = new ReflectionClass(XRegistryAuth::class);

        $this->assertEquals($conf, $rc->getStaticPropertyValue('exportConfigurations'));
    }
}
