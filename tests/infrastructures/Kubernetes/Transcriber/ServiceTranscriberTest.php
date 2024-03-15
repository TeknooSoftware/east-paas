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
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Service;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Transport;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\ServiceTranscriber;
use Teknoo\Kubernetes\Client as KubeClient;
use Teknoo\Kubernetes\Repository\ServiceRepository;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\ServiceTranscriber
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\CommonTrait
 */
class ServiceTranscriberTest extends TestCase
{
    public function buildTranscriber(): ServiceTranscriber
    {
        return new ServiceTranscriber();
    }

    public function testRun()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);


        $cd->expects(self::once())
            ->method('foreachService')
            ->willReturnCallback(function (callable $callback) use ($cd) {
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

        $repoService->expects(self::exactly(2))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(false, true);

        $repoService->expects(self::exactly(2))
            ->method('apply')
            ->willReturn(['foo']);

        $repoService->expects(self::once())
            ->method('delete')
            ->willReturn(['foo']);

        $kubeClient->expects(self::atLeastOnce())
            ->method('setNamespace')
            ->with('default_namespace');

        $kubeClient->expects(self::any())
            ->method('__call')
            ->willReturnMap([
                ['services', [], $repoService],
            ]);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::exactly(2))->method('success')->with(['foo']);
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            ServiceTranscriber::class,
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
            ->method('foreachService')
            ->willReturnCallback(function (callable $callback) use ($cd) {
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
        $kubeClient->expects(self::any())
            ->method('__call')
            ->with('services')
            ->willReturn($repo);

        $repo->expects(self::exactly(2))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(false, true);

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

        $repo->expects(self::once())
            ->method('delete')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success')->with(['foo']);
        $promise->expects(self::once())->method('fail');

        self::assertInstanceOf(
            ServiceTranscriber::class,
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