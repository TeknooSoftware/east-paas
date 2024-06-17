<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Kubernetes\Transcriber;

use DomainException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Container;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheck;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheckType;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\Image;
use Teknoo\East\Paas\Compilation\CompiledDeployment\MapReference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Resource;
use Teknoo\East\Paas\Compilation\CompiledDeployment\ResourceSet;
use Teknoo\East\Paas\Compilation\CompiledDeployment\SecretReference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\UpgradeStrategy;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\Reference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\MapVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\PersistentVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\SecretVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\CommonTrait;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\PodsTranscriberTrait;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\StatefulSetsTranscriber;
use Teknoo\Kubernetes\Client as KubeClient;
use Teknoo\Kubernetes\Collection\PodCollection;
use Teknoo\Kubernetes\Model\Deployment;
use Teknoo\Kubernetes\Model\Pod as PodModel;
use Teknoo\Kubernetes\Repository\PodRepository;
use Teknoo\Kubernetes\Repository\StatefulSetRepository;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(PodsTranscriberTrait::class)]
#[CoversClass(CommonTrait::class)]
#[CoversClass(StatefulSetsTranscriber::class)]
class StatefulSetsTranscriberTest extends TestCase
{
    public function buildTranscriber(): StatefulSetsTranscriber
    {
        return new StatefulSetsTranscriber();
    }

    public function testRun()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects($this->once())
            ->method('foreachPod')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $image1 = new Image('foo', '/foo', true, '7.4', ['foo' => 'bar']);
                $image1 = $image1->withRegistry('repository.teknoo.run');
                $image2 = new Image('bar', '/bar', true, '7.4', []);
                $image2 = $image2->withRegistry('repository.teknoo.run');

                $volume1 = new Volume('foo1', ['foo' => 'bar'], '/foo', '/mount');
                $volume2 = new Volume('bar1', ['bar' => 'foo'], '/bar', '/mount');

                $c1 = new Container(
                    'c1',
                    'foo',
                    '7.4',
                    [80],
                    ['foo' => $volume1->import('/foo')],
                    ['foo' => 'bar', 'bar' => 'foo'],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Command,
                        command: ['ps', 'aux', 'php'],
                        path: null,
                        port: null,
                        isSecure: null,
                        successThreshold: 4,
                        failureThreshold: 7,
                    ),
                    new ResourceSet([
                        new Resource('cpu', '100m', '200m'),
                        new Resource('cpu', '100m', '200m'),
                    ]),
                );
                $c2 = new Container(
                    'c2',
                    'bar',
                    '7.4',
                    [80],
                    [
                        'bar' => $volume2->import('/bar'),
                        'data' => new PersistentVolume('foo', 'bar'),
                        'vault' => new SecretVolume('foo', '/secret', 'bar'),
                        'map' => new MapVolume('foo', '/map', 'bar'),
                    ],
                    [
                        'foo' => 'bar',
                        'secret' => new SecretReference('foo', 'bar'),
                        'map' => new MapReference('foo', 'bar'),
                        'secret2' => new SecretReference('foo', null, true),
                        'map2' => new MapReference('foo', null, true),
                    ],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Http,
                        command: null,
                        path: '/status',
                        port: 8080,
                        isSecure: true,
                        successThreshold: 3,
                        failureThreshold: 6,
                    ),
                    new ResourceSet(),
                );
                $c3 = new Container(
                    'c3',
                    'alpine',
                    '3.16',
                    [8080],
                    [],
                    ['foo' => 'bar', 'bar' => 'foo'],
                    null,
                    new ResourceSet([
                        new Resource('cpu', '100m', '200m'),
                        new Resource('cpu', '100m', '200m'),
                    ]),
                );
                $c4 = new Container(
                    'c4',
                    'alpine',
                    '3.16',
                    [8080],
                    [],
                    [],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Tcp,
                        command: null,
                        path: null,
                        port: 8080,
                        isSecure: null,
                        successThreshold: 1,
                        failureThreshold: 2,
                    ),
                    new ResourceSet(),
                );

                $pod1 = new Pod('p1', 1, [$c1], isStateless: false);
                $pod2 = new Pod(
                    'p2',
                    1,
                    [$c2, $c3, $c4],
                    upgradeStrategy: UpgradeStrategy::Recreate,
                    fsGroup: 1000,
                    requires: ['x86_64', 'avx'],
                    isStateless: false,
                );
                $pod3 = new Pod(
                    'p3',
                    1,
                    [$c2, $c3, $c4],
                    upgradeStrategy: UpgradeStrategy::Recreate,
                    fsGroup: 1000,
                    requires: ['x86_64', 'avx'],
                    isStateless: true,
                );

                $callback($pod1, ['foo' => ['7.4' => $image1]], ['foo' => $volume1], 'a-prefix');
                $callback(
                    $pod2,
                    [
                        'bar' => ['7.4' => $image2]
                    ],
                    [
                        'bar' => $volume2,
                        'data' => new PersistentVolume('foo', 'bar'),
                        'vault' => new SecretVolume('foo', '/secret', 'bar'),
                        'map' => new MapVolume('bar', '/bar', 'bar'),
                    ],
                    'a-prefix',
                );
                $callback(
                    $pod2,
                    [
                    ],
                    [
                    ],
                    'a-prefix',
                );
                $callback(
                    $pod3,
                    [
                    ],
                    [
                    ],
                    'a-prefix',
                );
                return $cd;
            });

        $sfsRepo = $this->createMock(StatefulSetRepository::class);
        $pRepo = $this->createMock(PodRepository::class);

        $kubeClient->expects($this->atLeastOnce())
            ->method('setNamespace')
            ->with('default_namespace');

        $kubeClient->expects($this->any())
            ->method('__call')
            ->willReturnMap([
                ['statefulsets', [], $sfsRepo],
                ['pods', [], $pRepo],
            ]);

        $sfsRepo->expects($this->any())
            ->method('setLabelSelector')
            ->willReturnSelf();

        $pRepo->expects($this->any())
            ->method('setLabelSelector')
            ->willReturnSelf();

        $sfsRepo->expects($this->exactly(3))
            ->method('first')
            ->willReturnOnConsecutiveCalls(
                null,
                new Deployment(['metadata' => ['name' => 'foo']]),
            );

        $pRepo->expects($this->any(3))
            ->method('find')
            ->willReturnOnConsecutiveCalls(
                new PodCollection([new PodModel(['metadata' => ['name' => 'foo']])]),
                null,
            );

        $sfsRepo->expects($this->exactly(3))
            ->method('apply')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(3))->method('success')->with(['foo']);
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            StatefulSetsTranscriber::class,
            $this->buildTranscriber()->transcribe(
                compiledDeployment: $cd,
                client: $kubeClient,
                promise: $promise,
                defaultsBag: $this->createMock(DefaultsBag::class),
                namespace: 'default_namespace',
                useHierarchicalNamespaces: false,
            )
        );
    }

    public function testRunWithOciRegistryConfigName()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects($this->once())
            ->method('foreachPod')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $image1 = new Image('foo', '/foo', true, '7.4', ['foo' => 'bar']);
                $image1 = $image1->withRegistry('repository.teknoo.run');
                $image2 = new Image('bar', '/bar', true, '7.4', []);
                $image2 = $image2->withRegistry('repository.teknoo.run');

                $volume1 = new Volume('foo1', ['foo' => 'bar'], '/foo', '/mount');
                $volume2 = new Volume('bar1', ['bar' => 'foo'], '/bar', '/mount');

                $c1 = new Container(
                    'c1',
                    'foo',
                    '7.4',
                    [80],
                    ['foo' => $volume1->import('/foo')],
                    ['foo' => 'bar', 'bar' => 'foo'],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Command,
                        command: ['ps', 'aux', 'php'],
                        path: null,
                        port: null,
                        isSecure: null,
                        successThreshold: 2,
                        failureThreshold: 5,
                    ),
                    new ResourceSet([
                        new Resource('cpu', '100m', '200m'),
                        new Resource('cpu', '100m', '200m'),
                    ]),
                );
                $c2 = new Container(
                    'c2',
                    'bar',
                    '7.4',
                    [80],
                    [
                        'bar' => $volume2->import('/bar'),
                        'data' => new PersistentVolume('foo', 'bar'),
                        'vault' => new SecretVolume('foo', '/secret', 'bar'),
                        'map' => new MapVolume('foo', '/secret', 'bar'),
                    ],
                    [
                        'foo' => 'bar',
                        'secret' => new SecretReference('foo', 'bar'),
                        'map' => new MapReference('foo', 'bar'),
                        'secret2' => new SecretReference('foo', null, true),
                        'map2' => new MapReference('foo', null, true),
                    ],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Http,
                        command: null,
                        path: '/status',
                        port: 8080,
                        isSecure: false,
                        successThreshold: 5,
                        failureThreshold: 3,
                    ),
                    new ResourceSet(),
                );
                $c3 = new Container(
                    'c4',
                    'alpine',
                    '3.16',
                    [8080],
                    [],
                    [],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Tcp,
                        command: null,
                        path: null,
                        port: 8080,
                        isSecure: null,
                        successThreshold: 4,
                        failureThreshold: 1,
                    ),
                    new ResourceSet([
                        new Resource('cpu', '100m', '200m'),
                        new Resource('cpu', '100m', '200m'),
                    ]),
                );

                $pod1 = new Pod(
                    name: 'p1',
                    replicas: 1,
                    containers: [$c1],
                    ociRegistryConfigName: 'foo',
                    isStateless: false,
                    upgradeStrategy: UpgradeStrategy::Recreate
                );
                $pod2 = new Pod(
                    name: 'p2',
                    replicas: 1,
                    containers: [$c2, $c3],
                    fsGroup: 1000,
                    requires: ['x86_64', 'avx'],
                    isStateless: false,
                    upgradeStrategy: UpgradeStrategy::Recreate,
                );

                $callback($pod1, ['foo' => ['7.4' => $image1]], ['foo' => $volume1], 'a-prefix');
                $callback(
                    $pod2,
                    [
                        'bar' => ['7.4' => $image2]
                    ],
                    [
                        'bar' => $volume2,
                        'data' => new PersistentVolume('foo', 'bar'),
                        'vault' => new SecretVolume('foo', '/secret', 'bar'),
                    ],
                    'a-prefix',
                );
                return $cd;
            });

        $sfsRepo = $this->createMock(StatefulSetRepository::class);
        $pRepo = $this->createMock(PodRepository::class);

        $kubeClient->expects($this->atLeastOnce())
            ->method('setNamespace')
            ->with('default_namespace');

        $kubeClient->expects($this->any())
            ->method('__call')
            ->willReturnMap([
                ['statefulsets', [], $sfsRepo],
                ['pods', [], $pRepo],
            ]);

        $sfsRepo->expects($this->any())
            ->method('setLabelSelector')
            ->willReturnSelf();

        $pRepo->expects($this->any())
            ->method('setLabelSelector')
            ->willReturnSelf();

        $sfsRepo->expects($this->exactly(2))
            ->method('first')
            ->willReturnOnConsecutiveCalls(
                null,
                new Deployment(['metadata' => ['name' => 'foo']]),
            );

        $pRepo->expects($this->any())
            ->method('find')
            ->willReturnOnConsecutiveCalls(
                new PodCollection([
                    new PodModel(['metadata' => ['name' => 'foo']]),
                    new PodModel(['metadata' => ['name' => 'foo']]),
                ]),
                new PodCollection([]),
            );

        $sfsRepo->expects($this->exactly(2))
            ->method('apply')
            ->willReturn(['foo']);

        $pRepo->expects($this->exactly(2))
            ->method('delete')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(2))->method('success')->with(['foo']);
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            StatefulSetsTranscriber::class,
            $this->buildTranscriber()->transcribe(
                compiledDeployment: $cd,
                client: $kubeClient,
                promise: $promise,
                defaultsBag: $this->createMock(DefaultsBag::class),
                namespace: 'default_namespace',
                useHierarchicalNamespaces: false,
            )
        );
    }

    public function testRunWithOciRegistryConfigNameAsReference()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects($this->once())
            ->method('foreachPod')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $image1 = new Image('foo', '/foo', true, '7.4', ['foo' => 'bar']);
                $image1 = $image1->withRegistry('repository.teknoo.run');
                $image2 = new Image('bar', '/bar', true, '7.4', []);
                $image2 = $image2->withRegistry('repository.teknoo.run');

                $volume1 = new Volume('foo1', ['foo' => 'bar'], '/foo', '/mount');
                $volume2 = new Volume('bar1', ['bar' => 'foo'], '/bar', '/mount');

                $c1 = new Container(
                    'c1',
                    'foo',
                    '7.4',
                    [80],
                    ['foo' => $volume1->import('/foo')],
                    ['foo' => 'bar', 'bar' => 'foo'],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Command,
                        command: ['ps', 'aux', 'php'],
                        path: null,
                        port: null,
                        isSecure: null,
                        successThreshold: 2,
                        failureThreshold: 5,
                    ),
                    new ResourceSet([
                        new Resource('cpu', '100m', '200m'),
                        new Resource('cpu', '100m', '200m'),
                    ]),
                );
                $c2 = new Container(
                    'c2',
                    'bar',
                    '7.4',
                    [80],
                    [
                        'bar' => $volume2->import('/bar'),
                        'data' => new PersistentVolume('foo', 'bar'),
                        'vault' => new SecretVolume('foo', '/secret', 'bar'),
                        'map' => new MapVolume('foo', '/secret', 'bar'),
                    ],
                    [
                        'foo' => 'bar',
                        'secret' => new SecretReference('foo', 'bar'),
                        'map' => new MapReference('foo', 'bar'),
                        'secret2' => new SecretReference('foo', null, true),
                        'map2' => new MapReference('foo', null, true),
                    ],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Http,
                        command: null,
                        path: '/status',
                        port: 8080,
                        isSecure: false,
                        successThreshold: 5,
                        failureThreshold: 3,
                    ),
                    new ResourceSet(),
                );
                $c3 = new Container(
                    'c4',
                    'alpine',
                    '3.16',
                    [8080],
                    [],
                    [],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Tcp,
                        command: null,
                        path: null,
                        port: 8080,
                        isSecure: null,
                        successThreshold: 4,
                        failureThreshold: 1,
                    ),
                    new ResourceSet([
                        new Resource('cpu', '100m', '200m'),
                        new Resource('cpu', '100m', '200m'),
                    ]),
                );

                $pod1 = new Pod(
                    name: 'p1',
                    replicas: 1,
                    containers: [$c1],
                    ociRegistryConfigName: new Reference('oci-registry-config-name'),
                    isStateless: false,
                    upgradeStrategy: UpgradeStrategy::Recreate
                );
                $pod2 = new Pod(
                    name: 'p2',
                    replicas: 1,
                    containers: [$c2, $c3],
                    fsGroup: 1000,
                    requires: ['x86_64', 'avx'],
                    isStateless: false,
                    upgradeStrategy: UpgradeStrategy::Recreate,
                );

                $callback($pod1, ['foo' => ['7.4' => $image1]], ['foo' => $volume1], 'a-prefix');
                $callback(
                    $pod2,
                    [
                        'bar' => ['7.4' => $image2]
                    ],
                    [
                        'bar' => $volume2,
                        'data' => new PersistentVolume('foo', 'bar'),
                        'vault' => new SecretVolume('foo', '/secret', 'bar'),
                    ],
                    'a-prefix',
                );
                return $cd;
            });

        $sfsRepo = $this->createMock(StatefulSetRepository::class);
        $pRepo = $this->createMock(PodRepository::class);

        $kubeClient->expects($this->atLeastOnce())
            ->method('setNamespace')
            ->with('default_namespace');

        $kubeClient->expects($this->any())
            ->method('__call')
            ->willReturnMap([
                ['statefulsets', [], $sfsRepo],
                ['pods', [], $pRepo],
            ]);

        $sfsRepo->expects($this->any())
            ->method('setLabelSelector')
            ->willReturnSelf();

        $pRepo->expects($this->any())
            ->method('setLabelSelector')
            ->willReturnSelf();

        $sfsRepo->expects($this->exactly(2))
            ->method('first')
            ->willReturnOnConsecutiveCalls(
                null,
                new Deployment(['metadata' => ['name' => 'foo']]),
            );

        $pRepo->expects($this->any())
            ->method('find')
            ->willReturnOnConsecutiveCalls(
                new PodCollection([
                    new PodModel(['metadata' => ['name' => 'foo']]),
                    new PodModel(['metadata' => ['name' => 'foo']]),
                ]),
                new PodCollection([]),
            );

        $sfsRepo->expects($this->exactly(2))
            ->method('apply')
            ->willReturn(['foo']);

        $pRepo->expects($this->exactly(2))
            ->method('delete')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(2))->method('success')->with(['foo']);
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            StatefulSetsTranscriber::class,
            $this->buildTranscriber()->transcribe(
                compiledDeployment: $cd,
                client: $kubeClient,
                promise: $promise,
                defaultsBag: $this->createMock(DefaultsBag::class),
                namespace: 'default_namespace',
                useHierarchicalNamespaces: false,
            )
        );
    }

    public function testErrorOnFetching()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects($this->once())
            ->method('foreachPod')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $image1 = new Image('foo', '/foo', true, '7.4', ['foo' => 'bar']);
                $image1 = $image1->withRegistry('repository.teknoo.run');
                $image2 = new Image('bar', '/bar', true, '7.4', []);
                $image2 = $image2->withRegistry('repository.teknoo.run');

                $volume1 = new Volume('foo1', ['foo' => 'bar'], '/foo', '/mount');
                $volume2 = new Volume('bar1', ['bar' => 'foo'], '/bar', '/mount');

                $c1 = new Container(
                    'c1',
                    'foo',
                    '7.4',
                    [80],
                    ['foo' => $volume1->import('/foo')],
                    [],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Command,
                        command: ['ps', 'aux', 'php'],
                        path: null,
                        port: null,
                        isSecure: null,
                        successThreshold: 12,
                        failureThreshold: 23,
                    ),
                    new ResourceSet(),
                );
                $c2 = new Container(
                    'c2',
                    'bar',
                    '7.4',
                    [80],
                    ['bar' => $volume2->import('/bar')],
                    [],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Http,
                        command: null,
                        path: '/status',
                        port: 8080,
                        isSecure: true,
                        successThreshold: 1,
                        failureThreshold: 2,
                    ),
                    new ResourceSet(),
                );
                $c3 = new Container(
                    'c4',
                    'alpine',
                    '3.16',
                    [8080],
                    [],
                    [],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Tcp,
                        command: null,
                        path: null,
                        port: 8080,
                        isSecure: null,
                        successThreshold: 1,
                        failureThreshold: 2,
                    ),
                    new ResourceSet(),
                );

                $pod1 = new Pod('p1', 1, [$c1], isStateless: false);
                $pod2 = new Pod('p2', 1, [$c2, $c3], upgradeStrategy: UpgradeStrategy::RollingUpgrade, fsGroup: 1000, isStateless: false);

                $callback($pod1, ['foo' => ['7.4' => $image1]], ['foo' => $volume1], 'a-prefix');
                $callback($pod2, ['bar' => ['7.4' => $image2]], ['bar' => $volume2], 'a-prefix');
                return $cd;
            });

        $repo = $this->createMock(StatefulSetRepository::class);
        $kubeClient->expects($this->any())
            ->method('__call')
            ->with('statefulsets')
            ->willReturn($repo);

        $repo->expects($this->any())
            ->method('setLabelSelector')
            ->willReturnSelf();

        $call = 0;
        $repo->expects($this->exactly(2))
            ->method('first')
            ->willReturnCallback(
                function () use (&$call) {
                    if (0 < $call++) {
                        throw new DomainException('foo');
                    }

                    return null;
                },
            );

        $repo->expects($this->once())
            ->method('apply')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success')->with(['foo']);
        $promise->expects($this->once())->method('fail');

        self::assertInstanceOf(
            StatefulSetsTranscriber::class,
            $this->buildTranscriber()->transcribe(
                compiledDeployment: $cd,
                client: $kubeClient,
                promise: $promise,
                defaultsBag: $this->createMock(DefaultsBag::class),
                namespace: 'default_namespace',
                useHierarchicalNamespaces: false,
            )
        );
    }

    public function testError()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects($this->once())
            ->method('foreachPod')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $image1 = new Image('foo', '/foo', true, '7.4', ['foo' => 'bar']);
                $image1 = $image1->withRegistry('repository.teknoo.run');
                $image2 = new Image('bar', '/bar', true, '7.4', []);
                $image2 = $image2->withRegistry('repository.teknoo.run');

                $volume1 = new Volume('foo1', ['foo' => 'bar'], '/foo', '/mount');
                $volume2 = new Volume('bar1', ['bar' => 'foo'], '/bar', '/mount');

                $c1 = new Container(
                    'c1',
                    'foo',
                    '7.4',
                    [80],
                    ['foo' => $volume1->import('/foo')],
                    [],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Command,
                        command: ['ps', 'aux', 'php'],
                        path: null,
                        port: null,
                        isSecure: null,
                        successThreshold: 1,
                        failureThreshold: 2,
                    ),
                    new ResourceSet(),
                );
                $c2 = new Container(
                    'c2',
                    'bar',
                    '7.4',
                    [80],
                    ['bar' => $volume2->import('/bar')],
                    [],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Http,
                        command: null,
                        path: '/status',
                        port: 8080,
                        isSecure: false,
                        successThreshold: 1,
                        failureThreshold: 2,
                    ),
                    new ResourceSet(),
                );

                $pod1 = new Pod('p1', 1, [$c1], isStateless: false);
                $pod2 = new Pod('p2', 1, [$c2], fsGroup: 1000, requires: ['x86_64', 'avx'], isStateless: false);

                $callback($pod1, ['foo' => ['7.4' => $image1]], ['foo' => $volume1], 'a-prefix');
                $callback($pod2, ['bar' => ['7.4' => $image2]], ['bar' => $volume2], 'a-prefix');
                return $cd;
            });

        $repo = $this->createMock(StatefulSetRepository::class);
        $kubeClient->expects($this->any())
            ->method('__call')
            ->with('statefulsets')
            ->willReturn($repo);

        $repo->expects($this->any())
            ->method('setLabelSelector')
            ->willReturnSelf();

        $repo->expects($this->exactly(2))
            ->method('first')
            ->willReturnOnConsecutiveCalls(
                null,
                new Deployment(['metadata' => ['name' => 'foo']]),
            );

        $call = 0;
        $repo->expects($this->exactly(2))
            ->method('apply')
            ->willReturnCallback(
                function () use (&$call) {
                    if (0 < $call++) {
                        throw new DomainException('foo');
                    }

                    return ['foo'];
                },
            );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success')->with(['foo']);
        $promise->expects($this->once())->method('fail');

        self::assertInstanceOf(
            StatefulSetsTranscriber::class,
            $this->buildTranscriber()->transcribe(
                compiledDeployment: $cd,
                client: $kubeClient,
                promise: $promise,
                defaultsBag: $this->createMock(DefaultsBag::class),
                namespace: 'default_namespace',
                useHierarchicalNamespaces: false,
            )
        );
    }
}