<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Object;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Teknoo\Tests\East\Website\Object\Traits\ObjectTestTrait;

/**
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Object\ClusterCredentials
 */
class ClusterCredentialsTest extends TestCase
{
    use ObjectTestTrait;

    /**
     * @return ClusterCredentials
     */
    public function buildObject(): ClusterCredentials
    {
        return new ClusterCredentials('fooName', 'certBar', 'fooBar', 'barFoo');
    }

    public function testGetName()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated(['name' => 'fooBar'])->getName()
        );
    }

    public function testToString()
    {
        self::assertEquals(
            'fooBar',
            (string) $this->generateObjectPopulated(['name' => 'fooBar'])
        );
    }

    public function testGetServerCertificate()
    {
        self::assertEquals(
            'certBar',
            $this->generateObjectPopulated()->getServerCertificate()
        );
    }

    public function testGetPrivateKey()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated()->getPrivateKey()
        );
    }

    public function testGetPublicKey()
    {
        self::assertEquals(
            'barFoo',
            $this->generateObjectPopulated()->getPublicKey()
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
                '@class' => ClusterCredentials::class,
                'id' => '123',
                'name' => '123',
                'server_certificate' => 'certBar',
                'private_key' => 'fooBar',
                'public_key' => 'barFoo',
            ]);

        self::assertInstanceOf(
            ClusterCredentials::class,
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
