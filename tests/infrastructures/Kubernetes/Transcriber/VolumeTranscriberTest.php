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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\Reference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\PersistentVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\VolumeTranscriber;
use Teknoo\Kubernetes\Client as KubeClient;
use Teknoo\Kubernetes\Repository\PersistentVolumeClaimRepository;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(VolumeTranscriber::class)]
class VolumeTranscriberTest extends TestCase
{
    public function buildTranscriber(): VolumeTranscriber
    {
        return new VolumeTranscriber();
    }

    public function testRun()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects($this->once())
            ->method('foreachVolume')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $callback('foo', new PersistentVolume('foo', 'foo', 'id', new Reference('storage-size')), 'a-prefix');
                $callback('foo', new PersistentVolume('foo', 'foo', new Reference('storage-provider'), 'bar'), 'a-prefix');
                $callback('foo', new PersistentVolume('foo', 'foo', 'id', 'bar', true), 'a-prefix');
                $callback('bar', new Volume('foo2', ['foo1' => 'bar'], 'bar', 'bar'), 'a-prefix');
                return $cd;
            });

        $seRepo = $this->createMock(PersistentVolumeClaimRepository::class);

        $kubeClient->expects($this->atLeastOnce())
            ->method('setNamespace')
            ->with('default_namespace');

        $kubeClient->expects($this->any())
            ->method('__call')
            ->willReturnMap([
                ['persistentVolumeClaims', [], $seRepo],
            ]);

        $seRepo->expects($this->exactly(3))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(false, true, true);

        $seRepo->expects($this->exactly(2))
            ->method('apply')
            ->willReturn(['foo']);

        $seRepo->expects($this->once())
            ->method('delete');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(3))
            ->method('success')
            ->with(
                $this->callback(
                    fn ($value) => match ($value) {
                        ['foo'] => true,
                        [] => true,
                        default => false,
                    }
                )
            );
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            VolumeTranscriber::class,
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
            ->method('foreachVolume')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $callback('foo', new PersistentVolume('foo', 'foo', 'id', 'bar'), 'a-prefix');
                return $cd;
            });

        $repo = $this->createMock(PersistentVolumeClaimRepository::class);
        $kubeClient->expects($this->any())
            ->method('__call')
            ->with('persistentVolumeClaims')
            ->willReturn($repo);

        $repo->expects($this->once())
            ->method('apply')
            ->willThrowException(new \Exception());

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        self::assertInstanceOf(
            VolumeTranscriber::class,
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
