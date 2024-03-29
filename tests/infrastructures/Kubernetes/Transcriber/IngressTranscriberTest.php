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
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Ingress;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\IngressPath;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\IngressTranscriber;
use Teknoo\Kubernetes\Client as KubeClient;
use Teknoo\Kubernetes\Repository\IngressRepository;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\IngressTranscriber
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\CommonTrait
 */
class IngressTranscriberTest extends TestCase
{
    public function buildTranscriber(): IngressTranscriber
    {
        return new IngressTranscriber('provider', 'foo', 80, ['foo' => 'bar']);
    }

    public function testRun()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects(self::once())
            ->method('foreachIngress')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $callback(
                    new Ingress(
                        'foo1',
                        'foo.com',
                        null,
                        'sr1',
                        80,
                        [],
                        null,
                        false
                    ),
                    'a-prefix',
                );
                $callback(
                    new Ingress(
                        'foo2',
                        'foo.com',
                        null,
                        null,
                        null,
                        [
                            new IngressPath('/foo', 'sr2', 90)
                        ],
                        'cert',
                        true,
                        ['annotations' => 'foo'],
                        ['alias.foo.com']
                    ),
                    'a-prefix',
                );

                return $cd;
            });

        $repoIngress = $this->createMock(IngressRepository::class);

        $kubeClient->expects(self::atLeastOnce())
            ->method('setNamespace')
            ->with('default_namespace');

        $kubeClient->expects(self::any())
            ->method('__call')
            ->willReturnMap([
                ['ingresses', [], $repoIngress],
            ]);

        $repoIngress->expects(self::exactly(2))
            ->method('apply')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::exactly(2))->method('success')->with(['foo']);
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            IngressTranscriber::class,
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
            ->method('foreachIngress')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $callback(
                    new Ingress(
                        'foo1',
                        'foo.com',
                        null,
                        'sr1',
                        80,
                        [],
                        null,
                        false
                    ),
                    'a-prefix',
                );
                $callback(
                    new Ingress(
                        'foo2',
                        'foo.com',
                        null,
                        null,
                        null,
                        [
                            new IngressPath('/foo', 'sr2', 90)
                        ],
                        'cert',
                        true,
                        ['annotations' => ['foo' => 'bar']],
                    ),
                    'a-prefix',
                );
                return $cd;
            });

        $repo = $this->createMock(IngressRepository::class);
        $kubeClient->expects(self::any())
            ->method('__call')
            ->with('ingresses')
            ->willReturn($repo);

        $counter = 0;
        $repo->expects(self::exactly(2))
            ->method('apply')
            ->willReturnCallback(function () use (&$counter) {
                if (0 === $counter) {
                    $counter++;
                    return ['foo'];
                }

                throw new \Exception('foo');
            });

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success')->with(['foo']);
        $promise->expects(self::once())->method('fail');

        self::assertInstanceOf(
            IngressTranscriber::class,
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