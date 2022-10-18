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

namespace Teknoo\Tests\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Maclof\Kubernetes\Client as KubeClient;
use Maclof\Kubernetes\Models\ReplicationController;
use Maclof\Kubernetes\Repositories\ReplicationControllerRepository;
use PHPUnit\Framework\TestCase;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Container;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\Image;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\SecretReference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\PersistentVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\SecretVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\ReplicationControllerTranscriber;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\ReplicationControllerTranscriber
 */
class ReplicationControllerTranscriberTest extends TestCase
{
    public function buildTranscriber(): ReplicationControllerTranscriber
    {
        return new ReplicationControllerTranscriber();
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

                $c1 = new Container('c1', 'foo', '7.4', [80], ['foo' => $volume1->import('/foo')], ['foo' => 'bar', 'bar' => 'foo']);
                $c2 = new Container(
                    'c2',
                    'bar',
                    '7.4',
                    [80],
                    [
                        'bar' => $volume2->import('/bar'),
                        'data' => new PersistentVolume('foo', 'bar'),
                        'vault' => new SecretVolume('foo', '/secret', 'bar'),
                    ],
                    [
                        'foo' => 'bar',
                        'secret' => new SecretReference('foo', 'bar'),
                    ]
                );

                $pod1 = new Pod('p1', 1, [$c1]);
                $pod2 = new Pod('p2', 1, [$c2]);

                $callback($pod1, ['foo' => ['7.4' => $image1]], ['foo' => $volume1], 'default_namespace');
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
                    'default_namespace'
                );
                return $cd;
            });

        $srRepo = $this->createMock(ReplicationControllerRepository::class);

        $kubeClient->expects(self::atLeastOnce())
            ->method('setNamespace')
            ->with('default_namespace');

        $kubeClient->expects(self::any())
            ->method('__call')
            ->willReturnMap([
                ['replicationControllers', [], $srRepo],
            ]);

        $srRepo->expects(self::any())
            ->method('setLabelSelector')
            ->willReturnSelf();

        $srRepo->expects(self::exactly(2))
            ->method('first')
            ->willReturnOnConsecutiveCalls(
                null,
                new ReplicationController(['metadata' => ['name' => 'foo']]),
            );

        $srRepo->expects(self::exactly(2))
            ->method('create')
            ->willReturn(['foo']);

        $srRepo->expects(self::never())
            ->method('update');

        $srRepo->expects(self::once())
            ->method('delete')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::exactly(2))->method('success')->with(['foo']);
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            ReplicationControllerTranscriber::class,
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

                $c1 = new Container('c1', 'foo', '7.4', [80], ['foo' => $volume1->import('/foo')], ['foo' => 'bar', 'bar' => 'foo']);
                $c2 = new Container(
                    'c2',
                    'bar',
                    '7.4',
                    [80],
                    [
                        'bar' => $volume2->import('/bar'),
                        'data' => new PersistentVolume('foo', 'bar'),
                        'vault' => new SecretVolume('foo', '/secret', 'bar'),
                    ],
                    [
                        'foo' => 'bar',
                        'secret' => new SecretReference('foo', 'bar'),
                    ]
                );

                $pod1 = new Pod('p1', 1, [$c1], 'foo');
                $pod2 = new Pod('p2', 1, [$c2]);

                $callback($pod1, ['foo' => ['7.4' => $image1]], ['foo' => $volume1], 'default_namespace');
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
                    'default_namespace'
                );
                return $cd;
            });

        $srRepo = $this->createMock(ReplicationControllerRepository::class);

        $kubeClient->expects(self::atLeastOnce())
            ->method('setNamespace')
            ->with('default_namespace');

        $kubeClient->expects(self::any())
            ->method('__call')
            ->willReturnMap([
                ['replicationControllers', [], $srRepo],
            ]);

        $srRepo->expects(self::any())
            ->method('setLabelSelector')
            ->willReturnSelf();

        $srRepo->expects(self::exactly(2))
            ->method('first')
            ->willReturnOnConsecutiveCalls(
                null,
                new ReplicationController(['metadata' => ['name' => 'foo']]),
            );

        $srRepo->expects(self::exactly(2))
            ->method('create')
            ->willReturn(['foo']);

        $srRepo->expects(self::never())
            ->method('update');

        $srRepo->expects(self::once())
            ->method('delete')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::exactly(2))->method('success')->with(['foo']);
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            ReplicationControllerTranscriber::class,
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

                $c1 = new Container('c1', 'foo', '7.4', [80], ['foo' => $volume1->import('/foo')], []);
                $c2 = new Container('c2', 'bar', '7.4', [80], ['bar' => $volume2->import('/bar')], []);

                $pod1 = new Pod('p1', 1, [$c1]);
                $pod2 = new Pod('p2', 1, [$c2]);

                $callback($pod1, ['foo' => ['7.4' => $image1]], ['foo' => $volume1], 'default_namespace');
                $callback($pod2, ['bar' => ['7.4' => $image2]], ['bar' => $volume2], 'default_namespace');
                return $cd;
            });

        $repo = $this->createMock(ReplicationControllerRepository::class);
        $kubeClient->expects(self::any())
            ->method('__call')
            ->with('replicationControllers')
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
            ->method('create')
            ->willReturn(['foo']);

        $repo->expects(self::never())
            ->method('update');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success')->with(['foo']);
        $promise->expects(self::once())->method('fail');

        self::assertInstanceOf(
            ReplicationControllerTranscriber::class,
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

                $c1 = new Container('c1', 'foo', '7.4', [80], ['foo' => $volume1->import('/foo')], []);
                $c2 = new Container('c2', 'bar', '7.4', [80], ['bar' => $volume2->import('/bar')], []);

                $pod1 = new Pod('p1', 1, [$c1]);
                $pod2 = new Pod('p2', 1, [$c2]);

                $callback($pod1, ['foo' => ['7.4' => $image1]], ['foo' => $volume1], 'default_namespace');
                $callback($pod2, ['bar' => ['7.4' => $image2]], ['bar' => $volume2], 'default_namespace');
                return $cd;
            });

        $repo = $this->createMock(ReplicationControllerRepository::class);
        $kubeClient->expects(self::any())
            ->method('__call')
            ->with('replicationControllers')
            ->willReturn($repo);

        $repo->expects(self::any())
            ->method('setLabelSelector')
            ->willReturnSelf();

        $repo->expects(self::exactly(2))
            ->method('first')
            ->willReturnOnConsecutiveCalls(
                null,
                new ReplicationController(['metadata' => ['name' => 'foo']]),
            );

        $call = 0;
        $repo->expects(self::exactly(2))
            ->method('create')
            ->willReturnCallback(
                function () use (&$call) {
                    if (0 < $call++) {
                        throw new \DomainException('foo');
                    }

                    return ['foo'];
                },
            );

        $repo->expects(self::never())
            ->method('update');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success')->with(['foo']);
        $promise->expects(self::once())->method('fail');

        self::assertInstanceOf(
            ReplicationControllerTranscriber::class,
            $this->buildTranscriber()->transcribe($cd, $kubeClient, $promise)
        );
    }
}