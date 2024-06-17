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
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Paas\Object\SshIdentity;
use Teknoo\East\Paas\Object\Traits\ExportConfigurationsTrait;
use Teknoo\Tests\East\Common\Object\Traits\ObjectTestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ExportConfigurationsTrait::class)]
#[CoversClass(SshIdentity::class)]
class SshIdentityTest extends TestCase
{
    use ObjectTestTrait;

    /**
     * @return SshIdentity
     */
    public function buildObject(): SshIdentity
    {
        return new SshIdentity('fooName', 'barFoo');
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

    public function testGetPrivateKey()
    {
        self::assertEquals(
            'barFoo',
            $this->generateObjectPopulated()->getPrivateKey()
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
                '@class' => SshIdentity::class,
                'id' => '123',
                'name' => 'fooName',
                'private_key' => 'barFoo',
            ]);

        self::assertInstanceOf(
            SshIdentity::class,
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
                '@class' => SshIdentity::class,
                'id' => '123',
                'name' => 'fooName',
            ]);

        self::assertInstanceOf(
            SshIdentity::class,
            $this->buildObject()->setId('123')->exportToMeData(
                $normalizer,
                ['groups' => 'api']
            )
        );
    }

    public function testSetExportConfiguration()
    {
        SshIdentity::setExportConfiguration($conf = ['name' => ['all']]);
        $rc = new ReflectionClass(SshIdentity::class);

        self::assertEquals(
            $conf,
            $rc->getStaticPropertyValue('exportConfigurations'),
        );
    }
}
