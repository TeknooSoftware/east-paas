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

use Teknoo\East\Paas\Compilation\CompiledDeployment\UpgradeStrategy;
use Teknoo\Kubernetes\Client as KubeClient;
use Teknoo\Kubernetes\Model\Deployment;
use Teknoo\Kubernetes\Repository\PodRepository;
use Teknoo\Kubernetes\Repository\DeploymentRepository;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheck;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheckType;
use Teknoo\East\Paas\Compilation\CompiledDeployment\MapReference;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Container;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\Image;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\SecretReference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\MapVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\PersistentVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\SecretVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\DeploymentTranscriber;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\DeploymentTranscriber
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\CommonTrait
 */
class DeploymentTranscriberTest extends TestCase
{
    public function buildTranscriber(): DeploymentTranscriber
    {
        return new DeploymentTranscriber();
    }

    public function testRun()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects(self::once())
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
                );
                $c3 = new Container(
                    'c3',
                    'alpine',
                    '3.16',
                    [8080],
                    [],
                    ['foo' => 'bar', 'bar' => 'foo'],
                    null,
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
                );

                $pod1 = new Pod('p1', 1, [$c1]);
                $pod2 = new Pod(
                    'p2',
                    1,
                    [$c2, $c3, $c4],
                    upgradeStrategy: UpgradeStrategy::Recreate,
                    fsGroup: 1000,
                    requires: ['x86_64', 'avx'],
                );

                $callback($pod1, ['foo' => ['7.4' => $image1]], ['foo' => $volume1], 'default_namespace', 'a-prefix');
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
                    'default_namespace',
                    'a-prefix',
                );
                $callback(
                    $pod2,
                    [
                    ],
                    [
                    ],
                    'default_namespace',
                    'a-prefix',
                );
                return $cd;
            });

        $rcRepo = $this->createMock(DeploymentRepository::class);
        $pRepo = $this->createMock(PodRepository::class);

        $kubeClient->expects(self::atLeastOnce())
            ->method('setNamespace')
            ->with('default_namespace');

        $kubeClient->expects(self::any())
            ->method('__call')
            ->willReturnMap([
                ['deployments', [], $rcRepo],
                ['pods', [], $pRepo],
            ]);

        $rcRepo->expects(self::any())
            ->method('setLabelSelector')
            ->willReturnSelf();

        $pRepo->expects(self::any())
            ->method('setLabelSelector')
            ->willReturnSelf();

        $rcRepo->expects(self::exactly(3))
            ->method('first')
            ->willReturnOnConsecutiveCalls(
                null,
                new Deployment(['metadata' => ['name' => 'foo']]),
            );

        $pRepo->expects(self::any(3))
            ->method('first')
            ->willReturnOnConsecutiveCalls(
                new \Teknoo\Kubernetes\Model\Pod(['metadata' => ['name' => 'foo']]),
                null,
            );

        $rcRepo->expects(self::exactly(3))
            ->method('apply')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::exactly(3))->method('success')->with(['foo']);
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            DeploymentTranscriber::class,
            $this->buildTranscriber()->transcribe($cd, $kubeClient, $promise)
        );
    }


    public function testRunWithOciRegistryConfigName()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects(self::once())
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
                );

                $pod1 = new Pod('p1', 1, [$c1], 'foo');
                $pod2 = new Pod('p2', 1, [$c2, $c3], fsGroup: 1000, requires: ['x86_64', 'avx'],);

                $callback($pod1, ['foo' => ['7.4' => $image1]], ['foo' => $volume1], 'default_namespace', 'a-prefix');
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
                    'default_namespace',
                    'a-prefix',
                );
                return $cd;
            });

        $rcRepo = $this->createMock(DeploymentRepository::class);
        $pRepo = $this->createMock(PodRepository::class);

        $kubeClient->expects(self::atLeastOnce())
            ->method('setNamespace')
            ->with('default_namespace');

        $kubeClient->expects(self::any())
            ->method('__call')
            ->willReturnMap([
                ['deployments', [], $rcRepo],
                ['pods', [], $pRepo],
            ]);

        $rcRepo->expects(self::any())
            ->method('setLabelSelector')
            ->willReturnSelf();

        $pRepo->expects(self::any())
            ->method('setLabelSelector')
            ->willReturnSelf();

        $rcRepo->expects(self::exactly(2))
            ->method('first')
            ->willReturnOnConsecutiveCalls(
                null,
                new Deployment(['metadata' => ['name' => 'foo']]),
            );

        $pRepo->expects(self::any())
            ->method('first')
            ->willReturnOnConsecutiveCalls(
                new \Teknoo\Kubernetes\Model\Pod(['metadata' => ['name' => 'foo']]),
                null,
            );

        $rcRepo->expects(self::exactly(2))
            ->method('apply')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::exactly(2))->method('success')->with(['foo']);
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            DeploymentTranscriber::class,
            $this->buildTranscriber()->transcribe($cd, $kubeClient, $promise)
        );
    }

    public function testErrorOnFetching()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects(self::once())
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
                );

                $pod1 = new Pod('p1', 1, [$c1]);
                $pod2 = new Pod('p2', 1, [$c2, $c3], upgradeStrategy: UpgradeStrategy::RollingUpgrade, fsGroup: 1000);

                $callback($pod1, ['foo' => ['7.4' => $image1]], ['foo' => $volume1], 'default_namespace', 'a-prefix');
                $callback($pod2, ['bar' => ['7.4' => $image2]], ['bar' => $volume2], 'default_namespace', 'a-prefix');
                return $cd;
            });

        $repo = $this->createMock(DeploymentRepository::class);
        $kubeClient->expects(self::any())
            ->method('__call')
            ->with('deployments')
            ->willReturn($repo);

        $repo->expects(self::any())
            ->method('setLabelSelector')
            ->willReturnSelf();

        $call = 0;
        $repo->expects(self::exactly(2))
            ->method('first')
            ->willReturnCallback(
                function () use (&$call) {
                    if (0 < $call++) {
                        throw new \DomainException('foo');
                    }

                    return null;
                },
            );

        $repo->expects(self::once())
            ->method('apply')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success')->with(['foo']);
        $promise->expects(self::once())->method('fail');

        self::assertInstanceOf(
            DeploymentTranscriber::class,
            $this->buildTranscriber()->transcribe($cd, $kubeClient, $promise)
        );
    }

    public function testError()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects(self::once())
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
                );

                $pod1 = new Pod('p1', 1, [$c1]);
                $pod2 = new Pod('p2', 1, [$c2], fsGroup: 1000, requires: ['x86_64', 'avx'],);

                $callback($pod1, ['foo' => ['7.4' => $image1]], ['foo' => $volume1], 'default_namespace', 'a-prefix');
                $callback($pod2, ['bar' => ['7.4' => $image2]], ['bar' => $volume2], 'default_namespace', 'a-prefix');
                return $cd;
            });

        $repo = $this->createMock(DeploymentRepository::class);
        $kubeClient->expects(self::any())
            ->method('__call')
            ->with('deployments')
            ->willReturn($repo);

        $repo->expects(self::any())
            ->method('setLabelSelector')
            ->willReturnSelf();

        $repo->expects(self::exactly(2))
            ->method('first')
            ->willReturnOnConsecutiveCalls(
                null,
                new Deployment(['metadata' => ['name' => 'foo']]),
            );

        $call = 0;
        $repo->expects(self::exactly(2))
            ->method('apply')
            ->willReturnCallback(
                function () use (&$call) {
                    if (0 < $call++) {
                        throw new \DomainException('foo');
                    }

                    return ['foo'];
                },
            );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success')->with(['foo']);
        $promise->expects(self::once())->method('fail');

        self::assertInstanceOf(
            DeploymentTranscriber::class,
            $this->buildTranscriber()->transcribe($cd, $kubeClient, $promise)
        );
    }
}