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

namespace Teknoo\Tests\East\Paas\Infrastructures\Kubernetes;

use DI\Container;
use DI\ContainerBuilder;
use Maclof\Kubernetes\Client as KubClient;
use Psr\Http\Client\ClientInterface;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Driver;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\ClientFactoryInterface;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\IngressTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\NamespaceTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\ReplicaSetTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\SecretTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\ServiceTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\VolumeTranscriber;
use Teknoo\East\Paas\Object\ClusterCredentials;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ContainerTest extends TestCase
{
    /**
     * @return Container
     * @throws \Exception
     */
    protected function buildContainer() : Container
    {
        $containerDefinition = new ContainerBuilder();
        $containerDefinition->addDefinitions(__DIR__.'/../../../infrastructures/Kubernetes/di.php');

        return $containerDefinition->build();
    }

    public function testClientFactoryInterface()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/tmp');
        $container->set('teknoo.east.paas.kubernetes.ssl.verify', true);

        self::assertInstanceOf(
            ClientFactoryInterface::class,
            $factory = $container->get(ClientFactoryInterface::class)
        );

        self::assertInstanceOf(
            KubClient::class,
            $factory('foo', null)
        );

        self::assertInstanceOf(
            KubClient::class,
            $factory(
                'foo',
                new ClusterCredentials(
                'certBar',
                'barFoo',
                'fooBar',
                'barFoo2',
                'barBar'
                )
            )
        );

        unset($factory);
    }

    public function testClientFactoryInterfaceWithClient()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/tmp');
        $container->set('teknoo.east.paas.kubernetes.ssl.verify', true);
        $container->set(
            'teknoo.east.paas.kubernetes.http.client',
            $this->createMock(ClientInterface::class),
        );

        self::assertInstanceOf(
            ClientFactoryInterface::class,
            $factory = $container->get(ClientFactoryInterface::class)
        );

        self::assertInstanceOf(
            KubClient::class,
            $factory('foo', null)
        );

        self::assertInstanceOf(
            KubClient::class,
            $factory(
                'foo',
                new ClusterCredentials(
                'certBar',
                'barFoo',
                'fooBar',
                'barFoo2',
                'barBar'
                )
            )
        );

        unset($factory);
    }

    public function testClient()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/foo');
        $container->set('teknoo.east.paas.kubernetes.ssl.verify', true);

        self::assertInstanceOf(
            Driver::class,
            $container->get(Driver::class)
        );
    }

    public function testDirectory()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/foo');
        $container->set('teknoo.east.paas.kubernetes.ssl.verify', true);

        self::assertInstanceOf(
            Directory::class,
            $container->get(Directory::class)
        );
    }

    public function testIngressTranscriberBadClass()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.kubernetes.ingress.default_ingress_class', 'foo');
        $container->set('teknoo.east.paas.kubernetes.ingress.default_service.name', 'foo');
        $container->set('teknoo.east.paas.kubernetes.ingress.default_service.port', 80);
        $container->set('teknoo.east.paas.kubernetes.ingress.default_annotations', []);

        $container->set(IngressTranscriber::class . ':class', \stdClass::class);
        $this->expectException(\DomainException::class);
        $container->get(IngressTranscriber::class);
    }

    public function testIngressTranscriber()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.kubernetes.ingress.default_ingress_class', 'foo');
        $container->set('teknoo.east.paas.kubernetes.ingress.default_service.name', 'foo');
        $container->set('teknoo.east.paas.kubernetes.ingress.default_service.port', 80);
        $container->set('teknoo.east.paas.kubernetes.ingress.default_annotations', []);

        self::assertInstanceOf(
            IngressTranscriber::class,
            $container->get(IngressTranscriber::class)
        );
    }

    public function testReplicaSetTranscriberBadClass()
    {
        $container = $this->buildContainer();
        $container->set(ReplicaSetTranscriber::class . ':class', \stdClass::class);
        $this->expectException(\DomainException::class);
        $container->get(ReplicaSetTranscriber::class);
    }

    public function testReplicaSetTranscriber()
    {
        $container = $this->buildContainer();
        self::assertInstanceOf(
            ReplicaSetTranscriber::class,
            $container->get(ReplicaSetTranscriber::class)
        );
    }

    public function testSecretTranscriberBadClass()
    {
        $container = $this->buildContainer();
        $container->set(SecretTranscriber::class . ':class', \stdClass::class);
        $this->expectException(\DomainException::class);
        $container->get(SecretTranscriber::class);
    }

    public function testSecretTranscriber()
    {
        $container = $this->buildContainer();
        self::assertInstanceOf(
            SecretTranscriber::class,
            $container->get(SecretTranscriber::class)
        );
    }

    public function testServiceTranscriberBadClass()
    {
        $container = $this->buildContainer();
        $container->set(ServiceTranscriber::class . ':class', \stdClass::class);
        $this->expectException(\DomainException::class);
        $container->get(ServiceTranscriber::class);
    }

    public function testServiceTranscriber()
    {
        $container = $this->buildContainer();
        self::assertInstanceOf(
            ServiceTranscriber::class,
            $container->get(ServiceTranscriber::class)
        );
    }

    public function testNamespaceTranscriberBadClass()
    {
        $container = $this->buildContainer();
        $container->set(NamespaceTranscriber::class . ':class', \stdClass::class);
        $this->expectException(\DomainException::class);
        $container->get(NamespaceTranscriber::class);
    }

    public function testNamespaceTranscriber()
    {
        $container = $this->buildContainer();
        self::assertInstanceOf(
            NamespaceTranscriber::class,
            $container->get(NamespaceTranscriber::class)
        );
    }

    public function testVolumeTranscriberBadClass()
    {
        $container = $this->buildContainer();
        $container->set(VolumeTranscriber::class . ':class', \stdClass::class);
        $this->expectException(\DomainException::class);
        $container->get(VolumeTranscriber::class);
    }

    public function testVolumeTranscriber()
    {
        $container = $this->buildContainer();
        self::assertInstanceOf(
            VolumeTranscriber::class,
            $container->get(VolumeTranscriber::class)
        );
    }
}
