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
use Maclof\Kubernetes\Repositories\PersistentVolumeClaimRepository;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\PersistentVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\VolumeTranscriber;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\VolumeTranscriber
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\CleaningTrait
 */
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

        $cd->expects(self::once())
            ->method('foreachVolume')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $callback('foo', new PersistentVolume('foo', 'foo', 'id', 'bar'), 'default_namespace');
                $callback('foo', new PersistentVolume('foo', 'foo', 'id', 'bar'), 'default_namespace');
                $callback('foo', new PersistentVolume('foo', 'foo', 'id', 'bar', true), 'default_namespace');
                $callback('bar', new Volume('foo2', ['foo1' => 'bar'], 'bar', 'bar'), 'default_namespace');
                return $cd;
            });

        $seRepo = $this->createMock(PersistentVolumeClaimRepository::class);

        $kubeClient->expects(self::atLeastOnce())
            ->method('setNamespace')
            ->with('default_namespace');

        $kubeClient->expects(self::any())
            ->method('__call')
            ->willReturnMap([
                ['persistentVolumeClaims', [], $seRepo],
            ]);

        $seRepo->expects(self::exactly(3))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(false, true, true);

        $seRepo->expects(self::exactly(2))
            ->method('create')
            ->willReturn(['foo']);

        $seRepo->expects(self::once())
            ->method('delete');

        $seRepo->expects(self::never())
            ->method('update');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::exactly(3))->method('success')->withConsecutive([['foo']], [[]], [['foo']]);
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            VolumeTranscriber::class,
            $this->buildTranscriber()->transcribe($cd, $kubeClient, $promise)
        );
    }

    public function testError()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects(self::once())
            ->method('foreachVolume')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $callback('foo', new PersistentVolume('foo', 'foo', 'id', 'bar'), 'default_namespace');
                $callback('bar', new Volume('foo2', ['foo1' => 'bar'], 'bar', 'bar'), 'default_namespace');
                return $cd;
            });

        $repo = $this->createMock(PersistentVolumeClaimRepository::class);
        $kubeClient->expects(self::any())
            ->method('__call')
            ->with('persistentVolumeClaims')
            ->willReturn($repo);

        $repo->expects(self::once())
            ->method('exists')
            ->willReturnOnConsecutiveCalls(false, true);

        $repo->expects(self::once())
            ->method('create')
            ->willThrowException(new \Exception());

        $repo->expects(self::never())
            ->method('update');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        self::assertInstanceOf(
            VolumeTranscriber::class,
            $this->buildTranscriber()->transcribe($cd, $kubeClient, $promise)
        );
    }
}
