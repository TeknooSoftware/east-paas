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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Maclof\Kubernetes\Client as KubeClient;
use Maclof\Kubernetes\Repositories\SecretRepository;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Secret;
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\SecretTranscriber;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\SecretTranscriber
 */
class SecretTranscriberTest extends TestCase
{
    public function buildTranscriber(): SecretTranscriber
    {
        return new SecretTranscriber();
    }

    public function testRun()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects(self::once())
            ->method('foreachSecret')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $callback(new Secret('foo', 'map', ['foo' => 'bar']), 'default_namespace');
                $callback(new Secret('foo2', 'map', ['foo1' => ['foo1' => 'bar', 'foo2' => 'base64:' . \base64_encode('bar')]]), 'default_namespace');
                $callback(new Secret('foo2', 'foo', ['bar']), 'default_namespace');
                return $cd;
            });

        $seRepo = $this->createMock(SecretRepository::class);

        $kubeClient->expects(self::atLeastOnce())
            ->method('setNamespace')
            ->with('default_namespace');

        $kubeClient->expects(self::any())
            ->method('__call')
            ->willReturnMap([
                ['secrets', [], $seRepo],
            ]);

        $seRepo->expects(self::exactly(2))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(false, true);

        $seRepo->expects(self::once())
            ->method('create')
            ->willReturn(['foo']);

        $seRepo->expects(self::once())
            ->method('update')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::exactly(2))->method('success')->with(['foo']);
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            SecretTranscriber::class,
            $this->buildTranscriber()->transcribe($cd, $kubeClient, $promise)
        );
    }

    public function testError()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects(self::once())
            ->method('foreachSecret')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $callback(new Secret('foo', 'map', ['foo' => 'bar']), 'default_namespace');
                $callback(new Secret('foo2', 'map', ['foo' => 'bar']), 'default_namespace');
                return $cd;
            });

        $repo = $this->createMock(SecretRepository::class);
        $kubeClient->expects(self::any())
            ->method('__call')
            ->with('secrets')
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

        self::assertInstanceOf(
            SecretTranscriber::class,
            $this->buildTranscriber()->transcribe($cd, $kubeClient, $promise)
        );
    }
}