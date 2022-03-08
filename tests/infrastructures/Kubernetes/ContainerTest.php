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
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Driver;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\ClientFactoryInterface;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\IngressTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\ReplicationControllerTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\SecretTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\ServiceTranscriber;
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

    public function testIngressTranscriber()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.kubernetes.default_ingress_class', 'foo');
        $container->set('teknoo.east.paas.kubernetes.default_service.name', 'foo');
        $container->set('teknoo.east.paas.kubernetes.default_service.port', 80);

        self::assertInstanceOf(
            IngressTranscriber::class,
            $container->get(IngressTranscriber::class)
        );
    }

    public function testReplicationControllerTranscriber()
    {
        $container = $this->buildContainer();
        self::assertInstanceOf(
            ReplicationControllerTranscriber::class,
            $container->get(ReplicationControllerTranscriber::class)
        );
    }

    public function testSecretTranscriber()
    {
        $container = $this->buildContainer();
        self::assertInstanceOf(
            SecretTranscriber::class,
            $container->get(SecretTranscriber::class)
        );
    }

    public function testServiceTranscriber()
    {
        $container = $this->buildContainer();
        self::assertInstanceOf(
            ServiceTranscriber::class,
            $container->get(ServiceTranscriber::class)
        );
    }
}
