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

namespace Teknoo\Tests\East\Paas\Infrastructures\Kubernetes;

use PHPUnit\Framework\Attributes\CoversClass;
use Teknoo\Kubernetes\Client as KubClient;
use Teknoo\Kubernetes\Client as KubernetesClient;
use Teknoo\Kubernetes\RepositoryRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Factory;
use Teknoo\East\Paas\Object\ClusterCredentials;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Factory::class)]
class FactoryTest extends TestCase
{
    public function buildFactory(?ClientInterface $client = null): Factory
    {
        return new Factory(
            \sys_get_temp_dir(),
            $client,
        );
    }

    public function testInvokeWithoutCredentials(): void
    {
        $factory = $this->buildFactory();

        $client = $factory('foo', null);

        $this->assertInstanceOf(KubClient::class, $client);

        unset($factory);
    }

    public function testInvokeWithClientCertificateOnException(): void
    {
        $factory = new Factory(
            '/../../../../lol/',
            null,
            false,
            30,
            fn () => throw new \RuntimeException(),
        );

        $credential = new ClusterCredentials(
            'foo',
            'privateBar',
            'publicBar'
        );

        $this->expectException(\RuntimeException::class);
        $factory(
            'foo',
            $credential,
        );
    }

    public function testInvokeWithClientCertificateOnBadPath(): void
    {
        $factory = new Factory(
            '/../../../../lol/',
            null,
            false,
            30,
            fn (): false => false,
        );

        $credential = new ClusterCredentials(
            'foo',
            'privateBar',
            'publicBar'
        );

        $this->expectException(\RuntimeException::class);
        $factory(
            'foo',
            $credential,
        );
    }

    public function testInvokeWithClientCertificate(): void
    {
        $factory = $this->buildFactory();

        $credential = new ClusterCredentials(
            clientCertificate: 'foo',
            clientKey: 'privateBar',
        );

        $client = $factory('foo', $credential);

        $this->assertInstanceOf(KubClient::class, $client);

        unset($factory);
    }

    public function testInvokeWithToken(): void
    {
        $factory = $this->buildFactory();

        $credential = new ClusterCredentials(
            caCertificate: 'caCert',
            token: 'privateBar',
        );

        $client = $factory('foo', $credential);

        $this->assertInstanceOf(KubClient::class, $client);

        unset($factory);
    }

    public function testInvokeWithUserCredentials(): void
    {
        $factory = $this->buildFactory();

        $credential = new ClusterCredentials(
            username: 'foo',
            password: 'bar'
        );

        $client = $factory('foo', $credential);

        $this->assertInstanceOf(KubClient::class, $client);

        unset($factory);
    }

    public function testInvokeWithUserCredentialsWithRegistry(): void
    {
        $factory = $this->buildFactory();

        $credential = new ClusterCredentials(
            '',
            '',
            'foo',
            'bar'
        );

        $client = $factory('foo', $credential, $this->createStub(RepositoryRegistry::class));

        $this->assertInstanceOf(KubClient::class, $client);

        unset($factory);
    }
}
