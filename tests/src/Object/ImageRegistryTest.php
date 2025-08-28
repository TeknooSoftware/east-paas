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
use stdClass;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Paas\Object\ImageRegistry;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\Tests\East\Common\Object\Traits\ObjectTestTrait;
use TypeError;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ImageRegistry::class)]
class ImageRegistryTest extends TestCase
{
    use ObjectTestTrait;

    public function buildObject(): ImageRegistry
    {
        return new ImageRegistry('fooBar', $this->createMock(IdentityInterface::class));
    }

    public function testGetApiUrl(): void
    {
        $this->assertEquals('fooBar', $this->generateObjectPopulated()->getApiUrl());
    }

    public function testGetIdentity(): void
    {
        $this->assertInstanceOf(IdentityInterface::class, $this->generateObjectPopulated()->getIdentity());
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
                '@class' => ImageRegistry::class,
                'id' => '123',
                'api_url' => 'fooBar',
                'identity' => $this->createMock(IdentityInterface::class),
            ]);

        $this->assertInstanceOf(ImageRegistry::class, $this->buildObject()->setId('123')->exportToMeData(
            $normalizer,
            ['foo' => 'bar']
        ));
    }
}
