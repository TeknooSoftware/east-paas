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
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Teknoo\Kubernetes\Client as KubeClient;
use Teknoo\Kubernetes\Repository\SecretRepository;
use PHPUnit\Framework\TestCase;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Secret;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\SecretTranscriber;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\SecretTranscriber
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\CommonTrait
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
                $callback(new Secret('foo', 'map', ['foo' => 'bar']), 'default_namespace', 'a-prefix');
                $callback(new Secret('foo2', 'map', ['foo' => 'bar'], 'tls'), 'default_namespace', 'a-prefix');
                $callback(new Secret('foo3', 'map', ['foo1' => ['foo1' => 'bar', 'foo2' => 'base64:' . \base64_encode('bar')]], 'foo'), 'default_namespace', 'a-prefix');
                $callback(new Secret('foo4', 'foo', ['bar'], 'tls'), 'default_namespace', 'a-prefix');
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

        $seRepo->expects(self::exactly(3))
            ->method('apply')
            ->willReturn([
                'foo' => 'bar',
                'metadata' => ['managedFields' =>['foo']],
                'data' => ['foo' => 'bar'],
            ]);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::exactly(3))->method('success')->with([
            'foo' => 'bar',
            'metadata' => ['managedFields' => '#removed#'],
            'data' => '#removed#',
        ]);
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
                $callback(new Secret('foo', 'map', ['foo' => 'bar']), 'default_namespace', 'a-prefix');
                return $cd;
            });

        $repo = $this->createMock(SecretRepository::class);
        $kubeClient->expects(self::any())
            ->method('__call')
            ->with('secrets')
            ->willReturn($repo);

        $repo->expects(self::once())
            ->method('apply')
            ->willThrowException(new \Exception());

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        self::assertInstanceOf(
            SecretTranscriber::class,
            $this->buildTranscriber()->transcribe($cd, $kubeClient, $promise)
        );
    }
}