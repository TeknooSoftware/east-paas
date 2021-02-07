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

namespace Teknoo\Tests\East\Paas\Infrastructures\Kubernetes;

use Maclof\Kubernetes\Client as KubClient;
use Maclof\Kubernetes\Client as KubernetesClient;
use Maclof\Kubernetes\RepositoryRegistry;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Factory;
use Teknoo\East\Paas\Object\ClusterCredentials;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Factory
 */
class FactoryTest extends TestCase
{
    public function buildFactory(): Factory
    {
        return new Factory(
            \sys_get_temp_dir(),
            false
        );
    }

    public function testInvokeWithoutCredentials()
    {
        $factory = $this->buildFactory();

        $client = $factory('foo', null);

        self::assertInstanceOf(KubClient::class, $client);

        unset($factory);
    }

    public function testInvokeWithServerCertificate()
    {
        $factory = $this->buildFactory();

        $credential = new ClusterCredentials(
            'foo',
            'privateBar',
            'publicBar'
        );

        $client = $factory('foo', $credential);

        self::assertInstanceOf(KubClient::class, $client);

        unset($factory);
    }

    public function testInvokeWithUserCredentials()
    {
        $factory = $this->buildFactory();

        $credential = new ClusterCredentials(
            '',
            '',
            '',
            'foo',
            'bar'
        );

        $client = $factory('foo', $credential);

        self::assertInstanceOf(KubClient::class, $client);

        unset($factory);
    }

    public function testInvokeWithServerCertificateWithRegistry()
    {
        $factory = $this->buildFactory();

        $credential = new ClusterCredentials(
            'foo',
            'privateBar',
            'publicBar'
        );

        $client = $factory('foo', $credential, $this->createMock(RepositoryRegistry::class));

        self::assertInstanceOf(KubClient::class, $client);

        unset($factory);
    }

    public function testInvokeWithUserCredentialsWithRegistry()
    {
        $factory = $this->buildFactory();

        $credential = new ClusterCredentials(
            '',
            '',
            '',
            'foo',
            'bar'
        );

        $client = $factory('foo', $credential, $this->createMock(RepositoryRegistry::class));

        self::assertInstanceOf(KubClient::class, $client);

        unset($factory);
    }
}

