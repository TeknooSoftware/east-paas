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
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Service;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Transport;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\ServiceTranscriber;
use Teknoo\Kubernetes\Client as KubeClient;
use Teknoo\Kubernetes\Repository\ServiceRepository;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ServiceTranscriber::class)]
class ServiceTranscriberTest extends TestCase
{
    public function buildTranscriber(): ServiceTranscriber
    {
        return new ServiceTranscriber();
    }

    public function testRun(): void
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);


        $cd->expects($this->once())
            ->method('foreachService')
            ->willReturnCallback(function (callable $callback) use ($cd): MockObject|Stub {
                $callback(
                    new Service('foo', 'foo', [80 => 8080], Transport::Udp, false),
                    'a-prefix',
                );
                $callback(
                    new Service('foo', 'foo', [81 => 8081], Transport::Tcp, true),
                    'a-prefix',
                );
                return $cd;
            });


        $repoService = $this->createMock(ServiceRepository::class);

        $repoService->expects($this->exactly(2))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(false, true);

        $repoService->expects($this->exactly(2))
            ->method('apply')
            ->willReturn(['foo']);

        $repoService->expects($this->once())
            ->method('delete')
            ->willReturn(['foo']);

        $kubeClient->expects($this->atLeastOnce())
            ->method('setNamespace')
            ->with('default_namespace');

        $kubeClient
            ->method('__call')
            ->willReturnMap([
                ['services', [], $repoService],
            ]);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(2))->method('success')->with(['foo']);
        $promise->expects($this->never())->method('fail');

        $this->assertInstanceOf(ServiceTranscriber::class, $this->buildTranscriber()->transcribe(
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
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachService')
            ->willReturnCallback(function (callable $callback) use ($cd): MockObject|Stub {
                $callback(
                    new Service('foo', 'foo', [80 => 8080], Transport::Tcp, false),
                    'a-prefix',
                );
                $callback(
                    new Service('foo', 'foo', [81 => 8081], Transport::Tcp, true),
                    '',
                );
                return $cd;
            });

        $repo = $this->createMock(ServiceRepository::class);
        $kubeClient
            ->expects($this->atLeastOnce())
            ->method('__call')
            ->with('services')
            ->willReturn($repo);

        $repo->expects($this->exactly(2))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(false, true);

        $counter = 0;
        $repo->expects($this->exactly(2))
            ->method('apply')
            ->willReturnCallback(function () use (&$counter): array {
                if (0 === $counter) {
                    ++$counter;
                    return ['foo'];
                }

                throw new Exception('foo');
            });

        $repo->expects($this->once())
            ->method('delete')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success')->with(['foo']);
        $promise->expects($this->once())->method('fail');

        $this->assertInstanceOf(ServiceTranscriber::class, $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            client: $kubeClient,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default_namespace',
            useHierarchicalNamespaces: false,
        ));
    }
}
