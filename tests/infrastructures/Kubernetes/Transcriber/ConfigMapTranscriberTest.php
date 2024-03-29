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

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Map;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\ConfigMapTranscriber;
use Teknoo\Kubernetes\Client as KubeClient;
use Teknoo\Kubernetes\Repository\ConfigMapRepository;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\ConfigMapTranscriber
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\CommonTrait
 */
class ConfigMapTranscriberTest extends TestCase
{
    public function buildTranscriber(): ConfigMapTranscriber
    {
        return new ConfigMapTranscriber();
    }

    public function testRun()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects(self::once())
            ->method('foreachMap')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $callback(new Map('foo', ['foo' => 'bar']), 'a-prefix');
                $callback(new Map('foo2', ['foo' => 'bar']), 'a-prefix');
                $callback(new Map('foo3', ['foo1' => ['foo1' => 'bar', 'foo2' => 'base64:' . \base64_encode('bar')]]), 'a-prefix');
                return $cd;
            });

        $seRepo = $this->createMock(ConfigMapRepository::class);

        $kubeClient->expects(self::atLeastOnce())
            ->method('setNamespace')
            ->with('default_namespace');

        $kubeClient->expects(self::any())
            ->method('__call')
            ->willReturnMap([
                ['configMaps', [], $seRepo],
            ]);

        $seRepo->expects(self::exactly(3))
            ->method('apply')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::exactly(3))->method('success')->with(['foo']);
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            ConfigMapTranscriber::class,
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

        $cd->expects(self::once())
            ->method('foreachMap')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $callback(new Map('foo', ['foo' => 'bar']), 'a-prefix');
                return $cd;
            });

        $repo = $this->createMock(ConfigMapRepository::class);
        $kubeClient->expects(self::any())
            ->method('__call')
            ->with('configMaps')
            ->willReturn($repo);

        $repo->expects(self::once())
            ->method('apply')
            ->willThrowException(new \Exception());

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        self::assertInstanceOf(
            ConfigMapTranscriber::class,
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