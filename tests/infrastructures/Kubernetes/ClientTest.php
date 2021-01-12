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

use Maclof\Kubernetes\Client as KubernetesClient;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Client;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\ClientFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\ExposingInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberCollectionInterface;
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

    private ?TranscriberCollectionInterface $transcribers = null;

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

    /**
     * @return TranscriberCollectionInterface|MockObject
     */
    private function getTranscriberCollection(): ?TranscriberCollectionInterface
    {
        if (!$this->transcribers instanceof TranscriberCollectionInterface) {
            $this->transcribers = $this->createMock(TranscriberCollectionInterface::class);
        }

        return $this->transcribers;
    }

    public function buildClient(): Client
    {
        return new Client(
            $this->getClientFactory(),
            $this->getTranscriberCollection()
        );
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

    public function testDeployWithoutConfiguration()
    {
        $this->getTranscriberCollection()
            ->expects(self::never())
            ->method('getIterator');

        $client = $this->buildClient();

        $promise = $this->createMock(PromiseInterface::class);

        $this->expectException(\RuntimeException::class);

        self::assertInstanceOf(
            Client::class,
            $client->deploy(
                $this->createMock(CompiledDeploymentInterface::class),
                $promise
            )
        );
    }

    public function testDeployWithConfiguration()
    {
        $c1 = $this->createMock(DeploymentInterface::class);
        $c1->expects(self::once())->method('transcribe');
        $c2 = $this->createMock(DeploymentInterface::class);
        $c2->expects(self::once())->method('transcribe');
        $c3 = $this->createMock(ExposingInterface::class);
        $c3->expects(self::never())->method('transcribe');
        $c4 = $this->createMock(ExposingInterface::class);
        $c4->expects(self::never())->method('transcribe');

        $this->getTranscriberCollection()
            ->expects(self::any())
            ->method('getIterator')
            ->willReturnCallback(function () use ($c1, $c2, $c3, $c4) {
                yield from [$c1, $c2, $c3, $c4];
            });

        $client = $this->buildClient();

        self::assertInstanceOf(
            Client::class,
            $client = $client->configure(
                'foo',
                $this->createMock(ClusterCredentials::class)
            )
        );

        $promise = $this->createMock(PromiseInterface::class);

        $cd = $this->createMock(CompiledDeploymentInterface::class);

        self::assertInstanceOf(
            Client::class,
            $client->deploy(
                $cd,
                $promise
            )
        );
    }

    public function testDeployWithConfigurationException()
    {
        $c1 = $this->createMock(DeploymentInterface::class);
        $c1->expects(self::once())->method('transcribe')->willReturnCallback(
            function (
                CompiledDeploymentInterface $compiledDeployment,
                KubernetesClient $client,
                PromiseInterface $promise
            ) {
                $promise->fail(new \RuntimeException('foo'));
            }
        );
        $c2 = $this->createMock(DeploymentInterface::class);
        $c2->expects(self::never())->method('transcribe');
        $c3 = $this->createMock(ExposingInterface::class);
        $c3->expects(self::never())->method('transcribe');
        $c4 = $this->createMock(ExposingInterface::class);
        $c4->expects(self::never())->method('transcribe');

        $this->getTranscriberCollection()
            ->expects(self::any())
            ->method('getIterator')
            ->willReturnCallback(function () use ($c1, $c2, $c3, $c4) {
                yield from [$c1, $c2, $c3, $c4];
            });

        $client = $this->buildClient();

        self::assertInstanceOf(
            Client::class,
            $client = $client->configure(
                'foo',
                $this->createMock(ClusterCredentials::class)
            )
        );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('fail');

        $cd = $this->createMock(CompiledDeploymentInterface::class);

        self::assertInstanceOf(
            Client::class,
            $client->deploy(
                $cd,
                $promise
            )
        );
    }

    public function testExposeWithoutConfiguration()
    {
        $this->getTranscriberCollection()
            ->expects(self::never())
            ->method('getIterator');

        $client = $this->buildClient();

        $promise = $this->createMock(PromiseInterface::class);

        $this->expectException(\RuntimeException::class);

        self::assertInstanceOf(
            Client::class,
            $client->expose(
                $this->createMock(CompiledDeploymentInterface::class),
                $promise
            )
        );
    }

    public function testExposeWithConfiguration()
    {
        $c1 = $this->createMock(DeploymentInterface::class);
        $c1->expects(self::never())->method('transcribe');
        $c2 = $this->createMock(DeploymentInterface::class);
        $c2->expects(self::never())->method('transcribe');
        $c3 = $this->createMock(ExposingInterface::class);
        $c3->expects(self::once())->method('transcribe');
        $c4 = $this->createMock(ExposingInterface::class);
        $c4->expects(self::once())->method('transcribe');

        $this->getTranscriberCollection()
            ->expects(self::any())
            ->method('getIterator')
            ->willReturnCallback(function () use ($c1, $c2, $c3, $c4) {
                yield from [$c1, $c2, $c3, $c4];
            });

        $client = $this->buildClient();

        self::assertInstanceOf(
            Client::class,
            $client = $client->configure(
                'foo',
                $this->createMock(ClusterCredentials::class)
            )
        );

        $promise = $this->createMock(PromiseInterface::class);

        $cd = $this->createMock(CompiledDeploymentInterface::class);

        self::assertInstanceOf(
            Client::class,
            $client->expose(
                $cd,
                $promise
            )
        );
    }

    public function testExposeWithConfigurationException()
    {
        $c1 = $this->createMock(DeploymentInterface::class);
        $c1->expects(self::never())->method('transcribe');
        $c2 = $this->createMock(DeploymentInterface::class);
        $c2->expects(self::never())->method('transcribe');
        $c3 = $this->createMock(ExposingInterface::class);
        $c3->expects(self::once())->method('transcribe')->willReturnCallback(
            function (
                CompiledDeploymentInterface $compiledDeployment,
                KubernetesClient $client,
                PromiseInterface $promise
            ) {
                $promise->fail(new \RuntimeException('foo'));
            }
        );
        $c4 = $this->createMock(ExposingInterface::class);
        $c4->expects(self::never())->method('transcribe');

        $this->getTranscriberCollection()
            ->expects(self::any())
            ->method('getIterator')
            ->willReturnCallback(function () use ($c1, $c2, $c3, $c4) {
                yield from [$c1, $c2, $c3, $c4];
            });

        $client = $this->buildClient();

        self::assertInstanceOf(
            Client::class,
            $client = $client->configure(
                'foo',
                $this->createMock(ClusterCredentials::class)
            )
        );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('fail');

        $cd = $this->createMock(CompiledDeploymentInterface::class);

        self::assertInstanceOf(
            Client::class,
            $client->expose(
                $cd,
                $promise
            )
        );
    }
}

