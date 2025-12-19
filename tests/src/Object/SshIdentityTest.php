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
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Paas\Object\SshIdentity;
use Teknoo\Tests\East\Common\Object\Traits\ObjectTestTrait;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(SshIdentity::class)]
class SshIdentityTest extends TestCase
{
    use ObjectTestTrait;

    public function buildObject(): SshIdentity
    {
        return new SshIdentity('fooName', 'barFoo');
    }

    public function testGetName(): void
    {
        $this->assertEquals('fooName', $this->generateObjectPopulated()->getName());
    }

    public function testToString(): void
    {
        $this->assertEquals('fooName', (string) $this->generateObjectPopulated());
    }

    public function testGetPrivateKey(): void
    {
        $this->assertEquals('barFoo', $this->generateObjectPopulated()->getPrivateKey());
    }

    public function testExportToMeDataBadNormalizer(): void
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->exportToMeData(new \stdClass(), []);
    }

    public function testExportToMeDataBadContext(): void
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->exportToMeData(
            $this->createStub(EastNormalizerInterface::class),
            new \stdClass()
        );
    }

    public function testExportToMe(): void
    {
        $normalizer = $this->createMock(EastNormalizerInterface::class);
        $normalizer->expects($this->once())
            ->method('injectData')
            ->with([
                '@class' => SshIdentity::class,
                'id' => '123',
                'name' => 'fooName',
                'private_key' => 'barFoo',
            ]);

        $this->assertInstanceOf(SshIdentity::class, $this->buildObject()->setId('123')->exportToMeData(
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
                '@class' => SshIdentity::class,
                'id' => '123',
                'name' => 'fooName',
            ]);

        $this->assertInstanceOf(SshIdentity::class, $this->buildObject()->setId('123')->exportToMeData(
            $normalizer,
            ['groups' => 'api']
        ));
    }

    public function testSetExportConfiguration(): void
    {
        SshIdentity::setExportConfiguration($conf = ['name' => ['all']]);
        $rc = new ReflectionClass(SshIdentity::class);

        $this->assertEquals($conf, $rc->getStaticPropertyValue('exportConfigurations'));
    }
}
