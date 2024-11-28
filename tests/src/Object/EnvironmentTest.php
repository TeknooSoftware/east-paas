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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Object;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\Tests\East\Common\Object\Traits\ObjectTestTrait;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Environment::class)]
class EnvironmentTest extends TestCase
{
    use ObjectTestTrait;

    /**
     * @return Environment
     */
    public function buildObject(): Environment
    {
        return new Environment('fooBar');
    }

    public function testGetName()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated()->getName()
        );
    }

    public function testToString()
    {
        self::assertEquals(
            'fooBar',
            (string) $this->generateObjectPopulated()
        );
    }

    public function testIsEqualToInvalidBadClass()
    {
        $promiseInvalid = $this->createMock(PromiseInterface::class);
        $promiseInvalid->expects($this->never())->method('success');
        $promiseInvalid->expects($this->once())->method('fail')
            ->with(new \LogicException('teknoo.east.paas.error.environment.not_equal'));

        self::assertInstanceOf(
            Environment::class,
            $this->buildObject()->isEqualTo(new \stdClass(), $promiseInvalid)
        );
    }


    public function testIsEqualToInvalid()
    {
        $promiseInvalid = $this->createMock(PromiseInterface::class);
        $promiseInvalid->expects($this->never())->method('success');
        $promiseInvalid->expects($this->once())->method('fail')
            ->with(new \LogicException('teknoo.east.paas.error.environment.not_equal'));

        self::assertInstanceOf(
            Environment::class,
            $this->buildObject()->isEqualTo(new Environment('barFoo'), $promiseInvalid)
        );
    }

    public function testIsEqualToValid()
    {
        $env = new Environment('fooBar');
        $promiseInvalid = $this->createMock(PromiseInterface::class);
        $promiseInvalid->expects($this->once())->method('success')->with($env);
        $promiseInvalid->expects($this->never())->method('fail');

        self::assertInstanceOf(
            Environment::class,
            $this->buildObject()->isEqualTo($env, $promiseInvalid)
        );
    }

    public function testIsEqualBadPromise()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->isEqualTo(null, new \stdClass());
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
                '@class' => Environment::class,
                'name' => 'fooBar',
            ]);

        self::assertInstanceOf(
            Environment::class,
            $this->buildObject()->exportToMeData(
                $normalizer,
                ['foo' => 'bar']
            )
        );
    }
}
