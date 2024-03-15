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

use Exception;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Driver;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\NamespaceTranscriber;
use Teknoo\Kubernetes\Client as KubeClient;
use Teknoo\Kubernetes\Repository\SubnamespaceAnchorRepository;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\NamespaceTranscriber
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\CommonTrait
 */
class NamespaceTranscriberTest extends TestCase
{
    public function buildTranscriber(): NamespaceTranscriber
    {
        return new NamespaceTranscriber();
    }

    public function testSetDriver()
    {
        $transcriber = $this->buildTranscriber();
        $transcriber2 = $transcriber->setDriver($this->createMock(Driver::class));

        self::assertInstanceOf(
            NamespaceTranscriber::class,
            $transcriber2,
        );

        self::assertNotSame(
            $transcriber,
            $transcriber2
        );
    }

    public function testRun()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects(self::never())
            ->method('withJobSettings');

        $snRepo = $this->createMock(SubnamespaceAnchorRepository::class);

        $kubeClient->expects(self::any())
            ->method('__call')
            ->willReturnMap([
                ['subnamespacesAnchors', [], $snRepo],
            ]);

        $snRepo->expects(self::never())
            ->method('apply');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::exactly(1))
            ->method('success')
            ->with(
                $this->callback(
                    fn ($value) => match ($value) {
                        [] => true,
                        default => false,
                    }
                )
            );
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            NamespaceTranscriber::class,
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

    public function testRunWithHNC()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects(self::once())
            ->method('withJobSettings')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $callback(1, 'prefix', 'myproject');

                return $cd;
            });

        $snRepo = $this->createMock(SubnamespaceAnchorRepository::class);

        $kubeClient->expects(self::any())
            ->method('__call')
            ->willReturnMap([
                ['subnamespacesAnchors', [], $snRepo],
            ]);

        $snRepo->expects(self::once())
            ->method('apply')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success')->with(['foo']);
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            NamespaceTranscriber::class,
            $this->buildTranscriber()->transcribe(
                compiledDeployment: $cd,
                client: $kubeClient,
                promise: $promise,
                defaultsBag: $this->createMock(DefaultsBag::class),
                namespace: 'default-namespace-foo',
                useHierarchicalNamespaces: true,
            )
        );
    }

    public function testRunWithHNCButNoChildNameSpace()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects(self::never())
            ->method('withJobSettings');

        $snRepo = $this->createMock(SubnamespaceAnchorRepository::class);

        $kubeClient->expects(self::any())
            ->method('__call')
            ->willReturnMap([
                ['subnamespacesAnchors', [], $snRepo],
            ]);

        $snRepo->expects(self::never())
            ->method('apply');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success')->with([]);
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            NamespaceTranscriber::class,
            $this->buildTranscriber()->transcribe(
                compiledDeployment: $cd,
                client: $kubeClient,
                promise: $promise,
                defaultsBag: $this->createMock(DefaultsBag::class),
                namespace: 'default',
                useHierarchicalNamespaces: false,
            )
        );
    }

    public function testErrorWithHNC()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects(self::once())
            ->method('withJobSettings')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $callback(1, 'prefix', 'myproject');

                return $cd;
            });

        $snRepo = $this->createMock(SubnamespaceAnchorRepository::class);

        $kubeClient->expects(self::any())
            ->method('__call')
            ->willReturnMap([
                ['subnamespacesAnchors', [], $snRepo],
            ]);

        $snRepo->expects(self::once())
            ->method('apply')
            ->willThrowException(new Exception());

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        self::assertInstanceOf(
            NamespaceTranscriber::class,
            $this->buildTranscriber()->transcribe(
                compiledDeployment: $cd,
                client: $kubeClient,
                promise: $promise,
                defaultsBag: $this->createMock(DefaultsBag::class),
                namespace: 'default-namespace-foo',
                useHierarchicalNamespaces: true,
            )
        );
    }
}