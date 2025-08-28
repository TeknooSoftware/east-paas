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
use Teknoo\East\Paas\Object\ClusterCredentials;
use Teknoo\Tests\East\Common\Object\Traits\ObjectTestTrait;
use TypeError;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ClusterCredentials::class)]
class ClusterCredentialsTest extends TestCase
{
    use ObjectTestTrait;

    public function buildObject(): ClusterCredentials
    {
        return new ClusterCredentials('caBar', 'certBar', 'keyFoo', 'barFoo', 'barFoo2', 'barBar');
    }

    public function testGetName(): void
    {
        $this->assertEquals('barFoo2', $this->generateObjectPopulated()->getName());
    }

    public function testToString(): void
    {
        $this->assertEquals('barFoo2', (string) $this->generateObjectPopulated());
    }

    public function testGetCaCertificate(): void
    {
        $this->assertEquals('caBar', $this->generateObjectPopulated()->getCaCertificate());
    }

    public function testGetClientCertificate(): void
    {
        $this->assertEquals('certBar', $this->generateObjectPopulated()->getClientCertificate());
    }

    public function testGetClientFoo(): void
    {
        $this->assertEquals('keyFoo', $this->generateObjectPopulated()->getClientKey());
    }

    public function testGetToken(): void
    {
        $this->assertEquals('barFoo', $this->generateObjectPopulated()->getToken());
    }

    public function testGetUsername(): void
    {
        $this->assertEquals('barFoo2', $this->generateObjectPopulated()->getUsername());
    }

    public function testGetPassword(): void
    {
        $this->assertEquals('barBar', $this->generateObjectPopulated()->getPassword());
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
            $this->createMock(EastNormalizerInterface::class),
            new stdClass()
        );
    }

    public function testExportToMe(): void
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

        $this->assertInstanceOf(ClusterCredentials::class, $this->buildObject()->setId('123')->exportToMeData(
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
                '@class' => ClusterCredentials::class,
                'id' => '123',
                'username' => 'barFoo2',
            ]);

        $this->assertInstanceOf(ClusterCredentials::class, $this->buildObject()->setId('123')->exportToMeData(
            $normalizer,
            ['groups' => 'api']
        ));
    }

    public function testSetExportConfiguration(): void
    {
        ClusterCredentials::setExportConfiguration($conf = ['name' => ['all']]);
        $rc = new ReflectionClass(ClusterCredentials::class);

        $this->assertEquals($conf, $rc->getStaticPropertyValue('exportConfigurations'));
    }
}
