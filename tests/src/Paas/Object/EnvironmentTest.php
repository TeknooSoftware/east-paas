<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Object;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\Tests\East\Website\Object\Traits\ObjectTestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Object\Environment
 */
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
            (string) $this->generateObjectPopulated(['name' => 'fooBar'])
        );
    }

    public function testIsEqualToInvalidBadClass()
    {
        $promiseInvalid = $this->createMock(PromiseInterface::class);
        $promiseInvalid->expects(self::never())->method('success');
        $promiseInvalid->expects(self::once())->method('fail')
            ->with(new \LogicException('teknoo.paas.error.environment.not_equal'));

        self::assertInstanceOf(
            Environment::class,
            $this->buildObject()->isEqualTo(new \stdClass(), $promiseInvalid)
        );
    }


    public function testIsEqualToInvalid()
    {
        $promiseInvalid = $this->createMock(PromiseInterface::class);
        $promiseInvalid->expects(self::never())->method('success');
        $promiseInvalid->expects(self::once())->method('fail')
            ->with(new \LogicException('teknoo.paas.error.environment.not_equal'));

        self::assertInstanceOf(
            Environment::class,
            $this->buildObject()->isEqualTo(new Environment('barFoo'), $promiseInvalid)
        );
    }

    public function testIsEqualToValid()
    {
        $env = new Environment('fooBar');
        $promiseInvalid = $this->createMock(PromiseInterface::class);
        $promiseInvalid->expects(self::once())->method('success')->with($env);
        $promiseInvalid->expects(self::never())->method('fail');

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
        $normalizer->expects(self::once())
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

    public function testSetDeletedAt()
    {
        self::markTestSkipped('Not implemented');
    }

    public function testSetDeletedAtExceptionOnBadArgument()
    {
        self::markTestSkipped('Not implemented');
    }

    public function testDeletedAt()
    {
        self::markTestSkipped('Not implemented');
    }
}
