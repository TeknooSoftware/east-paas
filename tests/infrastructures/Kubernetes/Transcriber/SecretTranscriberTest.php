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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Kubernetes\Transcriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Secret;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\SecretTranscriber;
use Teknoo\Kubernetes\Client as KubeClient;
use Teknoo\Kubernetes\Repository\SecretRepository;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(SecretTranscriber::class)]
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

        $cd->expects($this->once())
            ->method('foreachSecret')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $callback(new Secret('foo', 'map', ['foo' => 'bar']), 'a-prefix');
                $callback(new Secret('foo2', 'map', ['foo' => 'bar'], 'tls'), 'a-prefix');
                $callback(new Secret('foo3', 'map', ['foo1' => ['foo1' => 'bar', 'foo2' => 'base64:' . \base64_encode('bar')]], 'foo'), 'a-prefix');
                $callback(new Secret('foo4', 'foo', ['bar'], 'tls'), 'a-prefix');
                return $cd;
            });

        $seRepo = $this->createMock(SecretRepository::class);

        $kubeClient->expects($this->atLeastOnce())
            ->method('setNamespace')
            ->with('default_namespace');

        $kubeClient->expects($this->any())
            ->method('__call')
            ->willReturnMap([
                ['secrets', [], $seRepo],
            ]);

        $seRepo->expects($this->exactly(3))
            ->method('apply')
            ->willReturn([
                'foo' => 'bar',
                'metadata' => ['managedFields' =>['foo']],
                'data' => ['foo' => 'bar'],
            ]);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(3))->method('success')->with([
            'foo' => 'bar',
            'metadata' => ['managedFields' => '#removed#'],
            'data' => '#removed#',
        ]);
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            SecretTranscriber::class,
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
            ->method('foreachSecret')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $callback(new Secret('foo', 'map', ['foo' => 'bar']), 'a-prefix');
                return $cd;
            });

        $repo = $this->createMock(SecretRepository::class);
        $kubeClient->expects($this->any())
            ->method('__call')
            ->with('secrets')
            ->willReturn($repo);

        $repo->expects($this->once())
            ->method('apply')
            ->willThrowException(new \Exception());

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        self::assertInstanceOf(
            SecretTranscriber::class,
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