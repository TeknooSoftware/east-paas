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
use Teknoo\East\Paas\Compilation\CompiledDeployment\Map;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\ConfigMapTranscriber;
use Teknoo\Kubernetes\Client as KubeClient;
use Teknoo\Kubernetes\Repository\ConfigMapRepository;
use Teknoo\Recipe\Promise\PromiseInterface;

use function base64_encode;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ConfigMapTranscriber::class)]
class ConfigMapTranscriberTest extends TestCase
{
    public function buildTranscriber(): ConfigMapTranscriber
    {
        return new ConfigMapTranscriber();
    }

    public function testRun(): void
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects($this->once())
            ->method('foreachMap')
            ->willReturnCallback(function (callable $callback) use ($cd): MockObject|Stub {
                $callback(new Map('foo', ['foo' => 'bar']), 'a-prefix');
                $callback(new Map('foo2', ['foo' => 'bar']), 'a-prefix');
                $callback(new Map('foo3', ['foo1' => ['foo1' => 'bar', 'foo2' => 'base64:' . base64_encode('bar')]]), 'a-prefix');
                return $cd;
            });

        $seRepo = $this->createMock(ConfigMapRepository::class);

        $kubeClient->expects($this->atLeastOnce())
            ->method('setNamespace')
            ->with('default_namespace');

        $kubeClient
            ->method('__call')
            ->willReturnMap([
                ['configMaps', [], $seRepo],
            ]);

        $seRepo->expects($this->exactly(3))
            ->method('apply')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(3))->method('success')->with(['foo']);
        $promise->expects($this->never())->method('fail');

        $this->assertInstanceOf(ConfigMapTranscriber::class, $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            client: $kubeClient,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default_namespace',
            useHierarchicalNamespaces: false,
        ));
    }

    public function testError(): void
    {
        $kubeClient = $this->createStub(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects($this->once())
            ->method('foreachMap')
            ->willReturnCallback(function (callable $callback) use ($cd): MockObject|Stub {
                $callback(new Map('foo', ['foo' => 'bar']), 'a-prefix');
                return $cd;
            });

        $repo = $this->createMock(ConfigMapRepository::class);
        $kubeClient
            ->method('__call')
            ->with('configMaps')
            ->willReturn($repo);

        $repo->expects($this->once())
            ->method('apply')
            ->willThrowException(new Exception());

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->assertInstanceOf(ConfigMapTranscriber::class, $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            client: $kubeClient,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default_namespace',
            useHierarchicalNamespaces: false,
        ));
    }
}
