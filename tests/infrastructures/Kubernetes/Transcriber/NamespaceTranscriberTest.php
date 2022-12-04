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

use Exception;
use Maclof\Kubernetes\Client as KubeClient;
use Maclof\Kubernetes\Repositories\NamespaceRepository;
use Maclof\Kubernetes\Repositories\SubnamespaceAnchorRepository;
use PHPUnit\Framework\TestCase;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\NamespaceTranscriber;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\NamespaceTranscriber
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\CleaningTrait
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
                $callback('default-namespace', false);
                $callback('default-namespace', false);
                return $cd;
            });

        $nsRepo = $this->createMock(NamespaceRepository::class);
        $snRepo = $this->createMock(SubnamespaceAnchorRepository::class);

        $kubeClient->expects(self::any())
            ->method('__call')
            ->willReturnMap([
                ['namespaces', [], $nsRepo],
                ['subnamespacesAnchors', [], $snRepo],
            ]);

        $nsRepo->expects(self::never())
            ->method('exists')
            ->willReturnOnConsecutiveCalls(false, true);

        $nsRepo->expects(self::never())
            ->method('create');

        $nsRepo->expects(self::never())
            ->method('update');

        $snRepo->expects(self::never())
            ->method('create')
            ->willReturn(['foo']);

        $snRepo->expects(self::never())
            ->method('update');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::exactly(2))->method('success')->withConsecutive([[]],  [[]]);
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            NamespaceTranscriber::class,
            $this->buildTranscriber()->transcribe($cd, $kubeClient, $promise)
        );
    }

    public function testRunWithHNC()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects(self::once())
            ->method('forNamespace')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $callback('default-namespace-foo', true);
                $callback('default-namespace-foo', true);
                return $cd;
            });

        $nsRepo = $this->createMock(NamespaceRepository::class);
        $snRepo = $this->createMock(SubnamespaceAnchorRepository::class);

        $kubeClient->expects(self::any())
            ->method('__call')
            ->willReturnMap([
                ['namespaces', [], $nsRepo],
                ['subnamespacesAnchors', [], $snRepo],
            ]);

        $nsRepo->expects(self::exactly(2))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(false, true);

        $nsRepo->expects(self::never())
            ->method('create');

        $nsRepo->expects(self::never())
            ->method('update');

        $snRepo->expects(self::once())
            ->method('create')
            ->willReturn(['foo']);

        $snRepo->expects(self::never())
            ->method('update');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::exactly(2))->method('success')->withConsecutive([['foo']],  [null]);
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            NamespaceTranscriber::class,
            $this->buildTranscriber()->transcribe($cd, $kubeClient, $promise)
        );
    }

    public function testRunWithHNCButNoChildNameSpace()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects(self::once())
            ->method('forNamespace')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $callback('default', true);
                return $cd;
            });

        $nsRepo = $this->createMock(NamespaceRepository::class);
        $snRepo = $this->createMock(SubnamespaceAnchorRepository::class);

        $kubeClient->expects(self::any())
            ->method('__call')
            ->willReturnMap([
                ['namespaces', [], $nsRepo],
                ['subnamespacesAnchors', [], $snRepo],
            ]);

        $nsRepo->expects(self::never())
            ->method('exists');

        $nsRepo->expects(self::never())
            ->method('create');

        $nsRepo->expects(self::never())
            ->method('update');

        $snRepo->expects(self::never())
            ->method('create');

        $snRepo->expects(self::never())
            ->method('update');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success')->with([]);
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            NamespaceTranscriber::class,
            $this->buildTranscriber()->transcribe($cd, $kubeClient, $promise)
        );
    }

    public function testErrorWithHNC()
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects(self::once())
            ->method('forNamespace')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $callback('default-namespace-foo', true);
                return $cd;
            });

        $nsRepo = $this->createMock(NamespaceRepository::class);
        $snRepo = $this->createMock(SubnamespaceAnchorRepository::class);

        $kubeClient->expects(self::any())
            ->method('__call')
            ->willReturnMap([
                ['namespaces', [], $nsRepo],
                ['subnamespacesAnchors', [], $snRepo],
            ]);

        $nsRepo->expects(self::exactly(1))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(false);

        $nsRepo->expects(self::never())
            ->method('create');

        $nsRepo->expects(self::never())
            ->method('update');

        $snRepo->expects(self::once())
            ->method('create')
            ->willThrowException(new Exception());

        $snRepo->expects(self::never())
            ->method('update');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success')->with(['foo']);
        $promise->expects(self::once())->method('fail');

        self::assertInstanceOf(
            NamespaceTranscriber::class,
            $this->buildTranscriber()->transcribe($cd, $kubeClient, $promise)
        );
    }
}