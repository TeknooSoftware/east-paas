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

namespace Teknoo\Tests\East\Paas\Object;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Paas\Object\Traits\ExportConfigurationsTrait;
use Teknoo\East\Paas\Object\XRegistryAuth;
use Teknoo\Tests\East\Common\Object\Traits\ObjectTestTrait;

/**
 * @author      Richard Déloge <richard@teknoo.software>
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversTrait(ExportConfigurationsTrait::class)]
#[CoversClass(XRegistryAuth::class)]
class XRegistryAuthTest extends TestCase
{
    use ObjectTestTrait;

    /**
     * @return XRegistryAuth
     */
    public function buildObject(): XRegistryAuth
    {
        return new XRegistryAuth('fooName', 'fooBar', 'barFoo', 'fooBar', 'barFoo');
    }

    public function testGetName()
    {
        self::assertEquals(
            'fooName',
            $this->generateObjectPopulated()->getName()
        );
    }

    public function testToString()
    {
        self::assertEquals(
            'fooName',
            (string) $this->generateObjectPopulated()
        );
    }

    public function testGetUsername()
    {
        self::assertEquals(
            'fooName',
            $this->generateObjectPopulated()->getUsername()
        );
    }

    public function testGetPassword()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated()->getPassword()
        );
    }

    public function testGetEmail()
    {
        self::assertEquals(
            'barFoo',
            $this->generateObjectPopulated()->getEmail()
        );
    }

    public function testGetAuth()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated()->getAuth()
        );
    }

    public function testGetConfigName()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated()->getConfigName()
        );
    }

    public function testGetServerAddress()
    {
        self::assertEquals(
            'barFoo',
            $this->generateObjectPopulated()->getServerAddress()
        );
    }

    public function testExportToMeDataBadNormalizer()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->exportToMeData(new \stdClass(), []);
    }

    public function testExportToMeDataBadContext()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->exportToMeData(
            $this->createMock(EastNormalizerInterface::class),
            new \stdClass()
        );
    }

    public function testExportToMe()
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

        self::assertInstanceOf(
            XRegistryAuth::class,
            $this->buildObject()->setId('123')->exportToMeData(
                $normalizer,
                ['foo' => 'bar']
            )
        );
    }

    public function testExportToMeApi()
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

        self::assertInstanceOf(
            XRegistryAuth::class,
            $this->buildObject()->setId('123')->exportToMeData(
                $normalizer,
                ['groups' => 'api']
            )
        );
    }

    public function testSetExportConfiguration()
    {
        XRegistryAuth::setExportConfiguration($conf = ['name' => ['all']]);
        $rc = new ReflectionClass(XRegistryAuth::class);

        self::assertEquals(
            $conf,
            $rc->getStaticPropertyValue('exportConfigurations'),
        );
    }
}
