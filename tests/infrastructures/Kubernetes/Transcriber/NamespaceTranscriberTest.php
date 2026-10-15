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

namespace Teknoo\Tests\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Driver;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\NamespaceTranscriber;
use Teknoo\Kubernetes\Client as KubeClient;
use Teknoo\Kubernetes\Model\SubnamespaceAnchor;
use Teknoo\Kubernetes\Repository\SubnamespaceAnchorRepository;
use Teknoo\Recipe\Promise\PromiseInterface;

use function strlen;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(NamespaceTranscriber::class)]
class NamespaceTranscriberTest extends TestCase
{
    public function buildTranscriber(): NamespaceTranscriber
    {
        return new NamespaceTranscriber();
    }

    public function testSetDriver(): void
    {
        $transcriber = $this->buildTranscriber();
        $transcriber2 = $transcriber->setDriver($this->createStub(Driver::class));

        $this->assertInstanceOf(NamespaceTranscriber::class, $transcriber2);

        $this->assertNotSame($transcriber, $transcriber2);
    }

    public function testRun(): void
    {
        $kubeClient = $this->createStub(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects($this->never())
            ->method('withJobSettings');

        $snRepo = $this->createMock(SubnamespaceAnchorRepository::class);

        $kubeClient
            ->method('__call')
            ->willReturnMap([
                ['subnamespacesAnchors', [], $snRepo],
            ]);

        $snRepo->expects($this->never())
            ->method('apply');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(1))
            ->method('success')
            ->with(
                $this->callback(
                    fn ($value): bool => match ($value) {
                        [] => true,
                        default => false,
                    }
                )
            );
        $promise->expects($this->never())->method('fail');

        $this->assertInstanceOf(NamespaceTranscriber::class, $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            client: $kubeClient,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default_namespace',
            useHierarchicalNamespaces: false,
        ));
    }

    public function testRunWithHNC(): void
    {
        $kubeClient = $this->createStub(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects($this->once())
            ->method('withJobSettings')
            ->willReturnCallback(function (callable $callback) use ($cd): MockObject|Stub {
                $callback(1, 'prefix', 'myproject');

                return $cd;
            });

        $snRepo = $this->createMock(SubnamespaceAnchorRepository::class);

        $kubeClient
            ->method('__call')
            ->willReturnMap([
                ['subnamespacesAnchors', [], $snRepo],
            ]);

        $snRepo->expects($this->once())
            ->method('apply')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success')->with(['foo']);
        $promise->expects($this->never())->method('fail');

        $this->assertInstanceOf(NamespaceTranscriber::class, $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            client: $kubeClient,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default-namespace-foo',
            useHierarchicalNamespaces: true,
        ));
    }

    public function testRunWithHNCButNoChildNameSpace(): void
    {
        $kubeClient = $this->createStub(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects($this->never())
            ->method('withJobSettings');

        $snRepo = $this->createMock(SubnamespaceAnchorRepository::class);

        $kubeClient
            ->method('__call')
            ->willReturnMap([
                ['subnamespacesAnchors', [], $snRepo],
            ]);

        $snRepo->expects($this->never())
            ->method('apply');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success')->with([]);
        $promise->expects($this->never())->method('fail');

        $this->assertInstanceOf(NamespaceTranscriber::class, $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            client: $kubeClient,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
            useHierarchicalNamespaces: false,
        ));
    }

    public function testErrorWithHNC(): void
    {
        $kubeClient = $this->createStub(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects($this->once())
            ->method('withJobSettings')
            ->willReturnCallback(function (callable $callback) use ($cd): MockObject|Stub {
                $callback(1, 'prefix', 'myproject');

                return $cd;
            });

        $snRepo = $this->createMock(SubnamespaceAnchorRepository::class);

        $kubeClient
            ->method('__call')
            ->willReturnMap([
                ['subnamespacesAnchors', [], $snRepo],
            ]);

        $snRepo->expects($this->once())
            ->method('apply')
            ->willThrowException(new Exception());

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->assertInstanceOf(NamespaceTranscriber::class, $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            client: $kubeClient,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default-namespace-foo',
            useHierarchicalNamespaces: true,
        ));
    }

    /**
     * @return array<string, mixed> the applied SubnamespaceAnchor
     */
    private function runWithHNC(string $namespace, string $projectName): array
    {
        $kubeClient = $this->createStub(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects($this->once())
            ->method('withJobSettings')
            ->willReturnCallback(function (callable $callback) use ($cd, $projectName): MockObject {
                $callback(1, 'prefix', $projectName);

                return $cd;
            });

        $manifest = [];
        $snRepo = $this->createMock(SubnamespaceAnchorRepository::class);
        $kubeClient->method('__call')->willReturnMap([['subnamespacesAnchors', [], $snRepo]]);
        $snRepo->expects($this->once())
            ->method('apply')
            ->willReturnCallback(
                function (SubnamespaceAnchor $model) use (&$manifest): array {
                    $manifest = $model->toArray();
                    return ['foo'];
                }
            );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success')->with(['foo']);
        $promise->expects($this->never())->method('fail');

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            client: $kubeClient,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: $namespace,
            useHierarchicalNamespaces: true,
        );

        return $manifest;
    }

    public function testRunWithHNCAndShortNameKeepsTheLabel(): void
    {
        $manifest = $this->runWithHNC('default-namespace-foo', 'myproject');

        $this->assertSame('myproject', $manifest['metadata']['name']);
        $this->assertSame(['name' => 'default-namespace-foo-myproject'], $manifest['metadata']['labels']);
    }

    public function testRunWithHNCAndLongNameBoundsTheLabel(): void
    {
        //67 characters, Kubernetes limits the value of a label to 63 characters
        $manifest = $this->runWithHNC('a-customer-namespace-on-a-shared-cluster', 'a-project-with-a-long-name');

        $this->assertSame('a-project-with-a-long-name', $manifest['metadata']['name']);
        $this->assertSame(
            ['name' => 'a-customer-namespace-on-a-shared-cluster-a-project-with-a-a2859'],
            $manifest['metadata']['labels'],
        );
        $this->assertSame(63, strlen($manifest['metadata']['labels']['name']));
    }
}
