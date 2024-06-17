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

namespace Teknoo\Tests\East\Paas\Infrastructures\Kubernetes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\ClientFactoryInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\ExposingInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberCollectionInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Driver;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Driver\Generator;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Driver\Running;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\NamespaceTranscriber;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Teknoo\Kubernetes\Client as KubernetesClient;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\States\Exception\MethodNotImplemented;
use TypeError;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Running::class)]
#[CoversClass(Generator::class)]
#[CoversClass(Driver::class)]
class DriverTest extends TestCase
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

    public function buildClient(): Driver
    {
        return new Driver(
            $this->getClientFactory(),
            $this->getTranscriberCollection()
        );
    }

    public function testConfigureWrongUrl()
    {
        $this->expectException(TypeError::class);
        $this->buildClient()->configure(
            new stdClass(),
            $this->createMock(IdentityInterface::class),
            $this->createMock(DefaultsBag::class),
            'namespace',
            false,
        );
    }

    public function testConfigureWrongAuth()
    {
        $this->expectException(TypeError::class);
        $this->buildClient()->configure(
            'foo',
            new stdClass(),
            $this->createMock(DefaultsBag::class),
            'namespace',
            false,
        );
    }

    public function testConfigureIdentityNotSupported()
    {
        $this->expectException(RuntimeException::class);
        $this->buildClient()->configure(
            'foo',
            $this->createMock(IdentityInterface::class),
            $this->createMock(DefaultsBag::class),
            'namespace',
            false,
        );
    }

    public function testConfigure()
    {
        self::assertInstanceOf(
            Driver::class,
            $this->buildClient()->configure(
                'foo',
                $this->createMock(ClusterCredentials::class),
                $this->createMock(DefaultsBag::class),
                'namespace',
                false,
            )
        );
    }

    public function testDeployWithoutConfiguration()
    {
        $this->getTranscriberCollection()
            ->expects($this->never())
            ->method('getIterator');

        $client = $this->buildClient();

        $promise = $this->createMock(PromiseInterface::class);

        $this->expectException(RuntimeException::class);

        self::assertInstanceOf(
            Driver::class,
            $client->deploy(
                $this->createMock(CompiledDeploymentInterface::class),
                $promise
            )
        );
    }

    public function testDeployWithConfiguration()
    {
        $c0 = $this->createMock(NamespaceTranscriber::class);
        $c0->expects($this->once())->method('setDriver')->willReturnSelf();
        $c0->expects($this->once())->method('transcribe');
        $c1 = $this->createMock(DeploymentInterface::class);
        $c1->expects($this->once())->method('transcribe');
        $c2 = $this->createMock(DeploymentInterface::class);
        $c2->expects($this->once())->method('transcribe');
        $c3 = $this->createMock(ExposingInterface::class);
        $c3->expects($this->never())->method('transcribe');
        $c4 = $this->createMock(ExposingInterface::class);
        $c4->expects($this->never())->method('transcribe');

        $this->getTranscriberCollection()
            ->expects($this->any())
            ->method('getIterator')
            ->willReturnCallback(function () use ($c0, $c1, $c2, $c3, $c4) {
                yield from [$c0, $c1, $c2, $c3, $c4];
            });

        $client = $this->buildClient();

        self::assertInstanceOf(
            Driver::class,
            $client = $client->configure(
                'foo',
                $this->createMock(ClusterCredentials::class),
                $this->createMock(DefaultsBag::class),
                'namespace',
                false,
            )
        );

        $promise = $this->createMock(PromiseInterface::class);

        $cd = $this->createMock(CompiledDeploymentInterface::class);

        self::assertInstanceOf(
            Driver::class,
            $client->deploy(
                $cd,
                $promise
            )
        );
    }

    public function testUpdateNamespaceWithNoConfiguration()
    {
        $client = $this->buildClient();

        $this->expectException(MethodNotImplemented::class);
        $client->updateNamespace(
            'foo'
        );
    }

    public function testUpdateNamespace()
    {
        $client = $this->buildClient();

        self::assertInstanceOf(
            Driver::class,
            $client = $client->configure(
                'foo',
                $this->createMock(ClusterCredentials::class),
                $this->createMock(DefaultsBag::class),
                'namespace',
                false,
            )
        );

        self::assertInstanceOf(
            Driver::class,
            $client->updateNamespace(
                'foo'
            )
        );
    }

    public function testDeployWithConfigurationException()
    {
        $c1 = $this->createMock(DeploymentInterface::class);
        $c1->expects($this->once())->method('transcribe')->willReturnCallback(
            function (
                CompiledDeploymentInterface $compiledDeployment,
                KubernetesClient $client,
                PromiseInterface $promise
            ) {
                $promise->fail(new RuntimeException('foo'));
            }
        );
        $c2 = $this->createMock(DeploymentInterface::class);
        $c2->expects($this->never())->method('transcribe');
        $c3 = $this->createMock(ExposingInterface::class);
        $c3->expects($this->never())->method('transcribe');
        $c4 = $this->createMock(ExposingInterface::class);
        $c4->expects($this->never())->method('transcribe');

        $this->getTranscriberCollection()
            ->expects($this->any())
            ->method('getIterator')
            ->willReturnCallback(function () use ($c1, $c2, $c3, $c4) {
                yield from [$c1, $c2, $c3, $c4];
            });

        $client = $this->buildClient();

        self::assertInstanceOf(
            Driver::class,
            $client = $client->configure(
                'foo',
                $this->createMock(ClusterCredentials::class),
                $this->createMock(DefaultsBag::class),
                'namespace',
                false,
            )
        );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('fail');

        $cd = $this->createMock(CompiledDeploymentInterface::class);

        self::assertInstanceOf(
            Driver::class,
            $client->deploy(
                $cd,
                $promise
            )
        );
    }

    public function testExposeWithoutConfiguration()
    {
        $this->getTranscriberCollection()
            ->expects($this->never())
            ->method('getIterator');

        $client = $this->buildClient();

        $promise = $this->createMock(PromiseInterface::class);

        $this->expectException(RuntimeException::class);

        self::assertInstanceOf(
            Driver::class,
            $client->expose(
                $this->createMock(CompiledDeploymentInterface::class),
                $promise
            )
        );
    }

    public function testExposeWithConfiguration()
    {
        $c1 = $this->createMock(DeploymentInterface::class);
        $c1->expects($this->never())->method('transcribe');
        $c2 = $this->createMock(DeploymentInterface::class);
        $c2->expects($this->never())->method('transcribe');
        $c3 = $this->createMock(ExposingInterface::class);
        $c3->expects($this->once())->method('transcribe');
        $c4 = $this->createMock(ExposingInterface::class);
        $c4->expects($this->once())->method('transcribe');

        $this->getTranscriberCollection()
            ->expects($this->any())
            ->method('getIterator')
            ->willReturnCallback(function () use ($c1, $c2, $c3, $c4) {
                yield from [$c1, $c2, $c3, $c4];
            });

        $client = $this->buildClient();

        self::assertInstanceOf(
            Driver::class,
            $client = $client->configure(
                'foo',
                $this->createMock(ClusterCredentials::class),
                $this->createMock(DefaultsBag::class),
                'namespace',
                false,
            )
        );

        $promise = $this->createMock(PromiseInterface::class);

        $cd = $this->createMock(CompiledDeploymentInterface::class);

        self::assertInstanceOf(
            Driver::class,
            $client->expose(
                $cd,
                $promise
            )
        );
    }

    public function testExposeWithConfigurationException()
    {
        $c1 = $this->createMock(DeploymentInterface::class);
        $c1->expects($this->never())->method('transcribe');
        $c2 = $this->createMock(DeploymentInterface::class);
        $c2->expects($this->never())->method('transcribe');
        $c3 = $this->createMock(ExposingInterface::class);
        $c3->expects($this->once())->method('transcribe')->willReturnCallback(
            function (
                CompiledDeploymentInterface $compiledDeployment,
                KubernetesClient $client,
                PromiseInterface $promise
            ) {
                $promise->fail(new RuntimeException('foo'));
            }
        );
        $c4 = $this->createMock(ExposingInterface::class);
        $c4->expects($this->never())->method('transcribe');

        $this->getTranscriberCollection()
            ->expects($this->any())
            ->method('getIterator')
            ->willReturnCallback(function () use ($c1, $c2, $c3, $c4) {
                yield from [$c1, $c2, $c3, $c4];
            });

        $client = $this->buildClient();

        self::assertInstanceOf(
            Driver::class,
            $client = $client->configure(
                'foo',
                $this->createMock(ClusterCredentials::class),
                $this->createMock(DefaultsBag::class),
                'namespace',
                false,
            )
        );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('fail');

        $cd = $this->createMock(CompiledDeploymentInterface::class);

        self::assertInstanceOf(
            Driver::class,
            $client->expose(
                $cd,
                $promise
            )
        );
    }
}

