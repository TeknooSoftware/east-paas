<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Object;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Paas\Object\XRegistryAuth;
use Teknoo\Tests\East\Website\Object\Traits\ObjectTestTrait;

/**
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Object\XRegistryAuth
 */
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
            'fooBar',
            $this->generateObjectPopulated(['username' => 'fooBar'])->getName()
        );
    }

    public function testToString()
    {
        self::assertEquals(
            'fooBar',
            (string) $this->generateObjectPopulated(['username' => 'fooBar'])
        );
    }

    public function testGetUsername()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated(['username' => 'fooBar'])->getUsername()
        );
    }

    public function testGetPassword()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated(['password' => 'fooBar'])->getPassword()
        );
    }

    public function testGetEmail()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated(['email' => 'fooBar'])->getEmail()
        );
    }

    public function testGetAuth()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated(['auth' => 'fooBar'])->getAuth()
        );
    }

    public function testGetServerAddress()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated(['serverAddress' => 'fooBar'])->getServerAddress()
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
