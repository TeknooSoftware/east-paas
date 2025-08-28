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
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\Tests\East\Common\Object\Traits\ObjectTestTrait;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Environment::class)]
class EnvironmentTest extends TestCase
{
    use ObjectTestTrait;

    public function buildObject(): Environment
    {
        return new Environment('fooBar');
    }

    public function testGetName(): void
    {
        $this->assertEquals('fooBar', $this->generateObjectPopulated()->getName());
    }

    public function testToString(): void
    {
        $this->assertEquals('fooBar', (string) $this->generateObjectPopulated());
    }

    public function testIsEqualToInvalidBadClass(): void
    {
        $promiseInvalid = $this->createMock(PromiseInterface::class);
        $promiseInvalid->expects($this->never())->method('success');
        $promiseInvalid->expects($this->once())->method('fail')
            ->with(new \LogicException('teknoo.east.paas.error.environment.not_equal'));

        $this->assertInstanceOf(Environment::class, $this->buildObject()->isEqualTo(new \stdClass(), $promiseInvalid));
    }


    public function testIsEqualToInvalid(): void
    {
        $promiseInvalid = $this->createMock(PromiseInterface::class);
        $promiseInvalid->expects($this->never())->method('success');
        $promiseInvalid->expects($this->once())->method('fail')
            ->with(new \LogicException('teknoo.east.paas.error.environment.not_equal'));

        $this->assertInstanceOf(Environment::class, $this->buildObject()->isEqualTo(new Environment('barFoo'), $promiseInvalid));
    }

    public function testIsEqualToValid(): void
    {
        $env = new Environment('fooBar');
        $promiseInvalid = $this->createMock(PromiseInterface::class);
        $promiseInvalid->expects($this->once())->method('success')->with($env);
        $promiseInvalid->expects($this->never())->method('fail');

        $this->assertInstanceOf(Environment::class, $this->buildObject()->isEqualTo($env, $promiseInvalid));
    }

    public function testIsEqualBadPromise(): void
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->isEqualTo(null, new \stdClass());
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
            $this->createMock(EastNormalizerInterface::class),
            new \stdClass()
        );
    }

    public function testExportToMe(): void
    {
        $normalizer = $this->createMock(EastNormalizerInterface::class);
        $normalizer->expects($this->once())
            ->method('injectData')
            ->with([
                '@class' => Environment::class,
                'name' => 'fooBar',
            ]);

        $this->assertInstanceOf(Environment::class, $this->buildObject()->exportToMeData(
            $normalizer,
            ['foo' => 'bar']
        ));
    }
}
