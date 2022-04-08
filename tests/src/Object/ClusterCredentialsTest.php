<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
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
use Teknoo\Tests\East\Common\Object\Traits\ObjectTestTrait;

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
        return new ClusterCredentials('certBar', 'barFoo', 'barFoo2', 'barBar');
    }

    public function testGetName()
    {
        self::assertEquals(
            'barFoo2',
            $this->generateObjectPopulated()->getName()
        );
    }

    public function testToString()
    {
        self::assertEquals(
            'barFoo2',
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

    public function testGetToken()
    {
        self::assertEquals(
            'barFoo',
            $this->generateObjectPopulated()->getToken()
        );
    }

    public function testGetUsername()
    {
        self::assertEquals(
            'barFoo2',
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
                'token' => 'barFoo',
                'username' => 'barFoo2',
                'password' => 'barBar',
            ]);

        self::assertInstanceOf(
            ClusterCredentials::class,
            $this->buildObject()->setId('123')->exportToMeData(
                $normalizer,
                ['foo' => 'bar']
            )
        );
    }
}
