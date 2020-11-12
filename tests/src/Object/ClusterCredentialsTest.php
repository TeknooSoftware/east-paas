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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Object;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Teknoo\Tests\East\Website\Object\Traits\ObjectTestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
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
        return new ClusterCredentials('certBar', 'barFoo', 'fooBar', 'barFoo', 'barBar');
    }

    public function testGetName()
    {
        self::assertEquals(
            'barFoo',
            $this->generateObjectPopulated()->getName()
        );
    }

    public function testToString()
    {
        self::assertEquals(
            'barFoo',
            (string) $this->generateObjectPopulated()
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
            'barFoo',
            $this->generateObjectPopulated()->getPrivateKey()
        );
    }

    public function testGetPublicKey()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated()->getPublicKey()
        );
    }

    public function testGetUsername()
    {
        self::assertEquals(
            'barFoo',
            $this->generateObjectPopulated()->getUsername()
        );
    }

    public function testGetPassword()
    {
        self::assertEquals(
            'barBar',
            $this->generateObjectPopulated()->getPassword()
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
                'server_certificate' => 'certBar',
                'private_key' => 'barFoo',
                'public_key' => 'fooBar',
                'username' => 'barFoo',
                'password' => 'fooBar',
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
