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
use stdClass;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Teknoo\East\Paas\Object\Traits\ExportConfigurationsTrait;
use Teknoo\Tests\East\Common\Object\Traits\ObjectTestTrait;
use TypeError;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversTrait(ExportConfigurationsTrait::class)]
#[CoversClass(ClusterCredentials::class)]
class ClusterCredentialsTest extends TestCase
{
    use ObjectTestTrait;

    /**
     * @return ClusterCredentials
     */
    public function buildObject(): ClusterCredentials
    {
        return new ClusterCredentials('caBar', 'certBar', 'keyFoo', 'barFoo', 'barFoo2', 'barBar');
    }

    public function testGetName()
    {
        self::assertEquals(
            'barFoo2',
            $this->generateObjectPopulated()->getName()
        );
    }

    public function testToString()
    {
        self::assertEquals(
            'barFoo2',
            (string) $this->generateObjectPopulated()
        );
    }

    public function testGetCaCertificate()
    {
        self::assertEquals(
            'caBar',
            $this->generateObjectPopulated()->getCaCertificate()
        );
    }

    public function testGetClientCertificate()
    {
        self::assertEquals(
            'certBar',
            $this->generateObjectPopulated()->getClientCertificate()
        );
    }

    public function testGetClientFoo()
    {
        self::assertEquals(
            'keyFoo',
            $this->generateObjectPopulated()->getClientKey()
        );
    }

    public function testGetToken()
    {
        self::assertEquals(
            'barFoo',
            $this->generateObjectPopulated()->getToken()
        );
    }

    public function testGetUsername()
    {
        self::assertEquals(
            'barFoo2',
            $this->generateObjectPopulated()->getUsername()
        );
    }

    public function testGetPassword()
    {
        self::assertEquals(
            'barBar',
            $this->generateObjectPopulated()->getPassword()
        );
    }

    public function testExportToMeDataBadNormalizer()
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->exportToMeData(new stdClass(), []);
    }

    public function testExportToMeDataBadContext()
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->exportToMeData(
            $this->createMock(EastNormalizerInterface::class),
            new stdClass()
        );
    }

    public function testExportToMe()
    {
        $normalizer = $this->createMock(EastNormalizerInterface::class);
        $normalizer->expects($this->once())
            ->method('injectData')
            ->with([
                '@class' => ClusterCredentials::class,
                'id' => '123',
                'ca_certificate' => 'caBar',
                'client_certificate' => 'certBar',
                'client_key' => 'keyFoo',
                'token' => 'barFoo',
                'username' => 'barFoo2',
                'password' => 'barBar',
            ]);

        self::assertInstanceOf(
            ClusterCredentials::class,
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
                '@class' => ClusterCredentials::class,
                'id' => '123',
                'username' => 'barFoo2',
            ]);

        self::assertInstanceOf(
            ClusterCredentials::class,
            $this->buildObject()->setId('123')->exportToMeData(
                $normalizer,
                ['groups' => 'api']
            )
        );
    }

    public function testSetExportConfiguration()
    {
        ClusterCredentials::setExportConfiguration($conf = ['name' => ['all']]);
        $rc = new ReflectionClass(ClusterCredentials::class);

        self::assertEquals(
            $conf,
            $rc->getStaticPropertyValue('exportConfigurations'),
        );
    }
}
