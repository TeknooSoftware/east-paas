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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Object;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Paas\Object\ImageRegistry;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\Tests\East\Common\Object\Traits\ObjectTestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Object\ImageRegistry
 */
class ImageRegistryTest extends TestCase
{
    use ObjectTestTrait;

    /**
     * @return ImageRegistry
     */
    public function buildObject(): ImageRegistry
    {
        return new ImageRegistry('fooBar', $this->createMock(IdentityInterface::class));
    }

    public function testGetApiUrl()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated()->getApiUrl()
        );
    }

    public function testGetIdentity()
    {
        self::assertInstanceOf(
            IdentityInterface::class,
            $this->generateObjectPopulated()->getIdentity()
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
        $normalizer->expects(self::once())
            ->method('injectData')
            ->with([
                '@class' => ImageRegistry::class,
                'id' => '123',
                'api_url' => 'fooBar',
                'identity' => $this->createMock(IdentityInterface::class),
            ]);

        self::assertInstanceOf(
            ImageRegistry::class,
            $this->buildObject()->setId('123')->exportToMeData(
                $normalizer,
                ['foo' => 'bar']
            )
        );
    }
}
