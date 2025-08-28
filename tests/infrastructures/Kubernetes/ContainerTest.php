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

use ArrayObject;
use DI\Container;
use DI\ContainerBuilder;
use DomainException;
use Exception;
use stdClass;
use Teknoo\East\Foundation\Time\SleepServiceInterface;
use Teknoo\East\Paas\DI\Exception\InvalidArgumentException;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\CronJobTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\JobTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\StatefulSetsTranscriber;
use Teknoo\Kubernetes\Client as KubClient;
use Psr\Http\Client\ClientInterface;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Driver;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\ClientFactoryInterface;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\ConfigMapTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\IngressTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\NamespaceTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\DeploymentTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\SecretTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\ServiceTranscriber;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\VolumeTranscriber;
use Teknoo\East\Paas\Object\ClusterCredentials;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ContainerTest extends TestCase
{
    /**
     * @throws Exception
     */
    protected function buildContainer(): Container
    {
        $containerDefinition = new ContainerBuilder();
        $containerDefinition->addDefinitions(__DIR__.'/../../../infrastructures/Kubernetes/di.php');

        return $containerDefinition->build();
    }

    public function testClientFactoryInterface(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/tmp');
        $container->set('teknoo.east.paas.kubernetes.ssl.verify', true);
        $container->set('teknoo.east.paas.kubernetes.timeout', 30);

        $this->assertInstanceOf(ClientFactoryInterface::class, $factory = $container->get(ClientFactoryInterface::class));

        $this->assertInstanceOf(KubClient::class, $factory('foo', null));

        $this->assertInstanceOf(KubClient::class, $factory(
            'foo',
            new ClusterCredentials(
                'certBar',
                'barFoo',
                'fooBar',
                'barFoo2',
                'barBar'
            )
        ));

        unset($factory);
    }

    public function testClientFactoryInterfaceWithClient(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/tmp');
        $container->set('teknoo.east.paas.kubernetes.ssl.verify', true);
        $container->set(
            'teknoo.east.paas.kubernetes.http.client',
            $this->createMock(ClientInterface::class),
        );

        $this->assertInstanceOf(ClientFactoryInterface::class, $factory = $container->get(ClientFactoryInterface::class));

        $this->assertInstanceOf(KubClient::class, $factory('foo', null));

        $this->assertInstanceOf(KubClient::class, $factory(
            'foo',
            new ClusterCredentials(
                'certBar',
                'barFoo',
                'fooBar',
                'barFoo2',
                'barBar'
            )
        ));

        unset($factory);
    }

    public function testClient(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/foo');
        $container->set('teknoo.east.paas.kubernetes.ssl.verify', true);
        $container->set(SleepServiceInterface::class, $this->createMock(SleepServiceInterface::class));

        $this->assertInstanceOf(Driver::class, $container->get(Driver::class));
    }

    public function testDirectory(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/foo');
        $container->set('teknoo.east.paas.kubernetes.ssl.verify', true);
        $container->set(SleepServiceInterface::class, $this->createMock(SleepServiceInterface::class));

        $this->assertInstanceOf(Directory::class, $container->get(Directory::class));
    }

    public function testIngressTranscriberBadClass(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.kubernetes.ingress.default_ingress_class', 'foo');
        $container->set('teknoo.east.paas.kubernetes.ingress.default_service.name', 'foo');
        $container->set('teknoo.east.paas.kubernetes.ingress.default_service.port', 80);
        $container->set('teknoo.east.paas.kubernetes.ingress.default_annotations', []);

        $container->set(IngressTranscriber::class . ':class', stdClass::class);
        $this->expectException(DomainException::class);
        $container->get(IngressTranscriber::class);
    }

    public function testIngressTranscriberWithEmptyAnnotations(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.kubernetes.ingress.default_ingress_class', 'foo');
        $container->set('teknoo.east.paas.kubernetes.ingress.default_service.name', 'foo');
        $container->set('teknoo.east.paas.kubernetes.ingress.default_service.port', 80);
        $container->set('teknoo.east.paas.kubernetes.ingress.default_annotations', []);

        $this->assertInstanceOf(IngressTranscriber::class, $container->get(IngressTranscriber::class));
    }

    public function testIngressTranscriberWithInvalidAnnotations(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.kubernetes.ingress.default_ingress_class', 'foo');
        $container->set('teknoo.east.paas.kubernetes.ingress.default_service.name', 'foo');
        $container->set('teknoo.east.paas.kubernetes.ingress.default_service.port', 80);
        $container->set('teknoo.east.paas.kubernetes.ingress.default_annotations', new stdClass());

        $this->expectException(InvalidArgumentException::class);
        $container->get(IngressTranscriber::class);
    }

    public function testIngressTranscriberWithFullAnnotations(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.kubernetes.ingress.default_ingress_class', 'foo');
        $container->set('teknoo.east.paas.kubernetes.ingress.default_service.name', 'foo');
        $container->set('teknoo.east.paas.kubernetes.ingress.default_service.port', 80);
        $container->set('teknoo.east.paas.kubernetes.ingress.default_annotations', ['foo' => 'bar']);

        $this->assertInstanceOf(IngressTranscriber::class, $container->get(IngressTranscriber::class));
    }

    public function testIngressTranscriberWithMissingAnnotations(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.kubernetes.ingress.default_ingress_class', 'foo');
        $container->set('teknoo.east.paas.kubernetes.ingress.default_service.name', 'foo');
        $container->set('teknoo.east.paas.kubernetes.ingress.default_service.port', 80);

        $this->assertInstanceOf(IngressTranscriber::class, $container->get(IngressTranscriber::class));
    }

    public function testIngressTranscriberWithIterableAnnotations(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.kubernetes.ingress.default_ingress_class', 'foo');
        $container->set('teknoo.east.paas.kubernetes.ingress.default_service.name', 'foo');
        $container->set('teknoo.east.paas.kubernetes.ingress.default_service.port', 80);
        $container->set('teknoo.east.paas.kubernetes.ingress.default_annotations', new ArrayObject(['foo' => 'bar']));

        $this->assertInstanceOf(IngressTranscriber::class, $container->get(IngressTranscriber::class));
    }

    public function testDeploymentTranscriberBadClass(): void
    {
        $container = $this->buildContainer();
        $container->set(DeploymentTranscriber::class . ':class', stdClass::class);
        $this->expectException(DomainException::class);
        $container->get(DeploymentTranscriber::class);
    }

    public function testDeploymentTranscriber(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.kubernetes.deployment.require_label', 'foo');
        $this->assertInstanceOf(DeploymentTranscriber::class, $container->get(DeploymentTranscriber::class));
    }

    public function testCronJobTranscriberBadClass(): void
    {
        $container = $this->buildContainer();
        $container->set(CronJobTranscriber::class . ':class', stdClass::class);
        $this->expectException(DomainException::class);
        $container->get(CronJobTranscriber::class);
    }

    public function testCronJobTranscriber(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.kubernetes.cronjob.require_label', 'foo');
        $container->set(SleepServiceInterface::class, $this->createMock(SleepServiceInterface::class));

        $this->assertInstanceOf(CronJobTranscriber::class, $container->get(CronJobTranscriber::class));
    }

    public function testJobTranscriberBadClass(): void
    {
        $container = $this->buildContainer();
        $container->set(JobTranscriber::class . ':class', stdClass::class);
        $this->expectException(DomainException::class);
        $container->get(JobTranscriber::class);
    }

    public function testJobTranscriber(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.kubernetes.job.require_label', 'foo');
        $container->set(SleepServiceInterface::class, $this->createMock(SleepServiceInterface::class));

        $this->assertInstanceOf(JobTranscriber::class, $container->get(JobTranscriber::class));
    }

    public function testStatefulSetsTranscriberBadClass(): void
    {
        $container = $this->buildContainer();
        $container->set(StatefulSetsTranscriber::class . ':class', stdClass::class);
        $this->expectException(DomainException::class);
        $container->get(StatefulSetsTranscriber::class);
    }

    public function testStatefulSetsTranscriber(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.kubernetes.statefulSets.require_label', 'foo');
        $this->assertInstanceOf(StatefulSetsTranscriber::class, $container->get(StatefulSetsTranscriber::class));
    }

    public function testSecretTranscriberBadClass(): void
    {
        $container = $this->buildContainer();
        $container->set(SecretTranscriber::class . ':class', stdClass::class);
        $this->expectException(DomainException::class);
        $container->get(SecretTranscriber::class);
    }

    public function testSecretTranscriber(): void
    {
        $container = $this->buildContainer();
        $this->assertInstanceOf(SecretTranscriber::class, $container->get(SecretTranscriber::class));
    }

    public function testConfigMapTranscriberBadClass(): void
    {
        $container = $this->buildContainer();
        $container->set(ConfigMapTranscriber::class . ':class', stdClass::class);
        $this->expectException(DomainException::class);
        $container->get(ConfigMapTranscriber::class);
    }

    public function testConfigMapTranscriber(): void
    {
        $container = $this->buildContainer();
        $this->assertInstanceOf(ConfigMapTranscriber::class, $container->get(ConfigMapTranscriber::class));
    }

    public function testServiceTranscriberBadClass(): void
    {
        $container = $this->buildContainer();
        $container->set(ServiceTranscriber::class . ':class', stdClass::class);
        $this->expectException(DomainException::class);
        $container->get(ServiceTranscriber::class);
    }

    public function testServiceTranscriber(): void
    {
        $container = $this->buildContainer();
        $this->assertInstanceOf(ServiceTranscriber::class, $container->get(ServiceTranscriber::class));
    }

    public function testNamespaceTranscriberBadClass(): void
    {
        $container = $this->buildContainer();
        $container->set(NamespaceTranscriber::class . ':class', stdClass::class);
        $this->expectException(DomainException::class);
        $container->get(NamespaceTranscriber::class);
    }

    public function testNamespaceTranscriber(): void
    {
        $container = $this->buildContainer();
        $this->assertInstanceOf(NamespaceTranscriber::class, $container->get(NamespaceTranscriber::class));
    }

    public function testVolumeTranscriberBadClass(): void
    {
        $container = $this->buildContainer();
        $container->set(VolumeTranscriber::class . ':class', stdClass::class);
        $this->expectException(DomainException::class);
        $container->get(VolumeTranscriber::class);
    }

    public function testVolumeTranscriber(): void
    {
        $container = $this->buildContainer();
        $this->assertInstanceOf(VolumeTranscriber::class, $container->get(VolumeTranscriber::class));
    }
}
