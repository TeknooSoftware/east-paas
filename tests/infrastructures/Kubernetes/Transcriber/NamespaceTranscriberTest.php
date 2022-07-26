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
use Maclof\Kubernetes\Repositories\NamespaceRepository;
use PHPUnit\Framework\TestCase;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\NamespaceTranscriber;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\NamespaceTranscriber
 */
class NamespaceTranscriberTest extends TestCase
{
    public function buildTranscriber(): NamespaceTranscriber
    {
        return new NamespaceTranscriber();
    }

    public function testRun()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects(self::once())
            ->method('forNamespace')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $callback('default_namespace');
                $callback('default_namespace');
                return $cd;
            });

        $seRepo = $this->createMock(NamespaceRepository::class);

        $kubeClient->expects(self::any())
            ->method('__call')
            ->willReturnMap([
                ['namespaces', [], $seRepo],
            ]);

        $seRepo->expects(self::exactly(2))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(false, true);

        $seRepo->expects(self::once())
            ->method('create')
            ->willReturn(['foo']);

        $seRepo->expects(self::never())
            ->method('update');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::exactly(2))->method('success')->withConsecutive([['foo']],  [null]);
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            NamespaceTranscriber::class,
            $this->buildTranscriber()->transcribe($cd, $kubeClient, $promise)
        );
    }

    public function testError()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects(self::once())
            ->method('forNamespace')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $callback('default_namespace');
                return $cd;
            });

        $repo = $this->createMock(NamespaceRepository::class);
        $kubeClient->expects(self::any())
            ->method('__call')
            ->with('namespaces')
            ->willReturn($repo);

        $repo->expects(self::exactly(1))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(false);

        $repo->expects(self::once())
            ->method('create')
            ->willThrowException(new \Exception());

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success')->with(['foo']);
        $promise->expects(self::once())->method('fail');

        self::assertInstanceOf(
            NamespaceTranscriber::class,
            $this->buildTranscriber()->transcribe($cd, $kubeClient, $promise)
        );
    }
}