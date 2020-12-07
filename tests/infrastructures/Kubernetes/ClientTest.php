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

use Teknoo\East\Paas\Container\PersistentVolume;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Client;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\ClientFactoryInterface;
use Maclof\Kubernetes\Client as KubeClient;
use Maclof\Kubernetes\Repositories\ReplicationControllerRepository;
use Maclof\Kubernetes\Repositories\ServiceRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Container\Container;
use Teknoo\East\Paas\Container\Image;
use Teknoo\East\Paas\Container\Pod;
use Teknoo\East\Paas\Container\Service;
use Teknoo\East\Paas\Container\Volume;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Paas\Object\ClusterCredentials;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Client
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Client\Generator
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Client\Running
 */
class ClientTest extends TestCase
{
    private ?ClientFactoryInterface $clientFactory = null;

    /**
     * @return ClientFactoryInterface|MockObject
     */
    private function getClientFactory(): ?ClientFactoryInterface
    {
        if (!$this->clientFactory instanceof ClientFactoryInterface) {
            $this->clientFactory = $this->createMock(ClientFactoryInterface::class);
        }

        return $this->clientFactory;
    }

    public function buildClient(): Client
    {
        return new Client($this->getClientFactory());
    }

    public function testConfigureWrongUrl()
    {
        $this->expectException(\TypeError::class);
        $this->buildClient()->configure(
            new \stdClass(),
            $this->createMock(IdentityInterface::class)
        );
    }

    public function testConfigureWrongAuth()
    {
        $this->expectException(\TypeError::class);
        $this->buildClient()->configure(
            'foo',
            new \stdClass()
        );
    }

    public function testConfigureIdentityNotSupported()
    {
        $this->expectException(\RuntimeException::class);
        $this->buildClient()->configure(
            'foo',
            $this->createMock(IdentityInterface::class)
        );
    }

    public function testConfigure()
    {
        self::assertInstanceOf(
            Client::class,
            $this->buildClient()->configure(
                'foo',
                $this->createMock(ClusterCredentials::class)
            )
        );
    }

    public function testDeployWrongCompileDeployment()
    {
        $this->expectException(\TypeError::class);
        $this->buildClient()->deploy(
            new \stdClass(),
            $this->createMock(PromiseInterface::class)
        );
    }

    public function testDeployWrongPromise()
    {
        $this->expectException(\TypeError::class);
        $this->buildClient()->deploy(
            $this->createMock(CompiledDeployment::class),
            new \stdClass()
        );
    }

    public function testDeployWithGenerator()
    {
        $this->expectException(\RuntimeException::class);

        $cd = $this->createMock(CompiledDeployment::class);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::never())->method('fail');

        $client = $this->buildClient();

        self::assertInstanceOf(
            Client::class,
            $client = $client->deploy(
                $cd,
                $promise
            )
        );
    }

    public function testDeploy()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeployment::class);

        $cd->expects(self::once())
            ->method('foreachPod')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $image1 = new Image('foo', '/foo', true, '7.4', ['foo' => 'bar']);
                $image1 = $image1->withRegistry('repository.teknoo.run');
                $image2 = new Image('bar', '/bar', true, '7.4', []);
                $image2 = $image2->withRegistry('repository.teknoo.run');

                $volume1 = new Volume('foo1', ['foo' => 'bar'], '/foo');
                $volume2 = new Volume('bar1', ['bar' => 'foo'], '/bar');

                $c1 = new Container('c1', 'foo', '7.4', [80], ['foo' => $volume1->import('/foo')], ['foo' => 'bar', 'bar' => 'foo']);
                $c2 = new Container('c2', 'bar', '7.4', [80], ['bar' => $volume2->import('/bar'), 'data' => new PersistentVolume('foo', 'bar')], []);

                $pod1 = new Pod('p1', 1, [$c1]);
                $pod2 = new Pod('p2', 1, [$c2]);

                $callback($pod1, ['foo' => ['7.4' => $image1]], ['foo' => $volume1]);
                $callback($pod2, ['bar' => ['7.4' => $image2]], ['bar' => $volume2, 'data' => new PersistentVolume('foo', 'bar')]);
                return $cd;
            });

        $this->getClientFactory()
            ->expects(self::any())
            ->method('__invoke')
            ->willReturn($kubeClient);

        $repo = $this->createMock(ReplicationControllerRepository::class);
        $kubeClient->expects(self::any())
            ->method('__call')
            ->with('replicationControllers')
            ->willReturn($repo);

        $repo->expects(self::exactly(2))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(false, true);

        $repo->expects(self::once())
            ->method('create')
            ->willReturn(['foo']);

        $repo->expects(self::once())
            ->method('update')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::exactly(2))->method('success')->with(['foo']);
        $promise->expects(self::never())->method('fail');

        $client = $this->buildClient();
        self::assertInstanceOf(
            Client::class,
            $client = $client->configure(
                'kube.teknoo.run',
                $this->createMock(ClusterCredentials::class)
            )
        );

        self::assertInstanceOf(
            Client::class,
            $client = $client->deploy(
                $cd,
                $promise
            )
        );
    }

    public function testDeployError()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeployment::class);

        $cd->expects(self::once())
            ->method('foreachPod')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $image1 = new Image('foo', '/foo', true, '7.4', ['foo' => 'bar']);
                $image1 = $image1->withRegistry('repository.teknoo.run');
                $image2 = new Image('bar', '/bar', true, '7.4', []);
                $image2 = $image2->withRegistry('repository.teknoo.run');

                $volume1 = new Volume('foo1', ['foo' => 'bar'], '/foo');
                $volume2 = new Volume('bar1', ['bar' => 'foo'], '/bar');

                $c1 = new Container('c1', 'foo', '7.4', [80], ['foo' => $volume1->import('/foo')], []);
                $c2 = new Container('c2', 'bar', '7.4', [80], ['bar' => $volume2->import('/bar')], []);

                $pod1 = new Pod('p1', 1, [$c1]);
                $pod2 = new Pod('p2', 1, [$c2]);

                $callback($pod1, ['foo' => ['7.4' => $image1]], ['foo' => $volume1]);
                $callback($pod2, ['bar' => ['7.4' => $image2]], ['bar' => $volume2]);
                return $cd;
            });

        $this->getClientFactory()
            ->expects(self::any())
            ->method('__invoke')
            ->willReturn($kubeClient);

        $repo = $this->createMock(ReplicationControllerRepository::class);
        $kubeClient->expects(self::any())
            ->method('__call')
            ->with('replicationControllers')
            ->willReturn($repo);

        $repo->expects(self::exactly(2))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(false, true);

        $repo->expects(self::once())
            ->method('create')
            ->willReturn(['foo']);

        $repo->expects(self::once())
            ->method('update')
            ->willThrowException(new \Exception());

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success')->with(['foo']);
        $promise->expects(self::once())->method('fail');

        $client = $this->buildClient();
        self::assertInstanceOf(
            Client::class,
            $client = $client->configure(
                'kube.teknoo.run',
                $this->createMock(ClusterCredentials::class)
            )
        );

        self::assertInstanceOf(
            Client::class,
            $client = $client->deploy(
                $cd,
                $promise
            )
        );
    }

    public function testExposeWrongCompileDeployment()
    {
        $this->expectException(\TypeError::class);
        $this->buildClient()->expose(
            new \stdClass(),
            $this->createMock(PromiseInterface::class)
        );
    }

    public function testExposeWrongPromise()
    {
        $this->expectException(\TypeError::class);
        $this->buildClient()->expose(
            $this->createMock(CompiledDeployment::class),
            new \stdClass()
        );
    }

    public function testExposeWithGenerator()
    {
        $this->expectException(\RuntimeException::class);

        $cd = $this->createMock(CompiledDeployment::class);

        $promise = $this->createMock(PromiseInterface::class);

        $client = $this->buildClient();

        self::assertInstanceOf(
            Client::class,
            $client = $client->expose(
                $cd,
                $promise
            )
        );
    }

    public function testExpose()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeployment::class);

        $cd->expects(self::once())
            ->method('foreachService')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $callback(new Service('foo', [80 => 8080], 'TCP'));
                $callback(new Service('foo', [81 => 8081], 'TCP'));
                return $cd;
            });

        $this->getClientFactory()
            ->expects(self::any())
            ->method('__invoke')
            ->willReturn($kubeClient);

        $repo = $this->createMock(ReplicationControllerRepository::class);
        $kubeClient->expects(self::any())
            ->method('__call')
            ->with('services')
            ->willReturn($repo);

        $repo->expects(self::exactly(2))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(false, true);

        $repo->expects(self::exactly(2))
            ->method('create')
            ->willReturn(['foo']);

        $repo->expects(self::once())
            ->method('delete')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::exactly(2))->method('success')->with(['foo']);
        $promise->expects(self::never())->method('fail');

        $client = $this->buildClient();
        self::assertInstanceOf(
            Client::class,
            $client = $client->configure(
                'kube.teknoo.run',
                $this->createMock(ClusterCredentials::class)
            )
        );

        self::assertInstanceOf(
            Client::class,
            $client = $client->expose(
                $cd,
                $promise
            )
        );
    }

    public function testExposeError()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeployment::class);

        $cd->expects(self::once())
            ->method('foreachService')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $callback(new Service('foo', [80 => 8080], 'TCP'));
                $callback(new Service('foo', [81 => 8081], 'TCP'));
                return $cd;
            });

        $this->getClientFactory()
            ->expects(self::any())
            ->method('__invoke')
            ->willReturn($kubeClient);

        $repo = $this->createMock(ServiceRepository::class);
        $kubeClient->expects(self::any())
            ->method('__call')
            ->with('services')
            ->willReturn($repo);

        $repo->expects(self::exactly(2))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(false, true);

        $counter = 0;
        $repo->expects(self::exactly(2))
            ->method('create')
            ->willReturnCallback(function () use (&$counter) {
                if (0 === $counter) {
                    $counter++;
                    return ['foo'];
                }

                throw new \Exception('foo');
            });

        $repo->expects(self::once())
            ->method('delete')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success')->with(['foo']);
        $promise->expects(self::once())->method('fail');

        $client = $this->buildClient();
        self::assertInstanceOf(
            Client::class,
            $client = $client->configure(
                'kube.teknoo.run',
                $this->createMock(ClusterCredentials::class)
            )
        );

        self::assertInstanceOf(
            Client::class,
            $client = $client->expose(
                $cd,
                $promise
            )
        );
    }
}

