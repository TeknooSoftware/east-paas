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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Exception;
use Teknoo\Kubernetes\Client as KubeClient;
use Teknoo\Kubernetes\Repository\SubnamespaceAnchorRepository;
use PHPUnit\Framework\TestCase;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\NamespaceTranscriber;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\NamespaceTranscriber
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\CommonTrait
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

        $snRepo = $this->createMock(SubnamespaceAnchorRepository::class);

        $kubeClient->expects(self::any())
            ->method('__call')
            ->willReturnMap([
                ['subnamespacesAnchors', [], $snRepo],
            ]);

        $snRepo->expects(self::never())
            ->method('apply');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::exactly(2))
            ->method('success')
            ->with(
                $this->callback(
                    fn ($value) => match ($value) {
                        [] => true,
                        default => false,
                    }
                )
            );
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

        $snRepo = $this->createMock(SubnamespaceAnchorRepository::class);

        $kubeClient->expects(self::any())
            ->method('__call')
            ->willReturnMap([
                ['subnamespacesAnchors', [], $snRepo],
            ]);

        $snRepo->expects(self::exactly(2))
            ->method('apply')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::exactly(2))->method('success')->with(['foo']);
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

        $snRepo = $this->createMock(SubnamespaceAnchorRepository::class);

        $kubeClient->expects(self::any())
            ->method('__call')
            ->willReturnMap([
                ['subnamespacesAnchors', [], $snRepo],
            ]);

        $snRepo->expects(self::never())
            ->method('apply');

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

        $snRepo = $this->createMock(SubnamespaceAnchorRepository::class);

        $kubeClient->expects(self::any())
            ->method('__call')
            ->willReturnMap([
                ['subnamespacesAnchors', [], $snRepo],
            ]);

        $snRepo->expects(self::once())
            ->method('apply')
            ->willThrowException(new Exception());

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        self::assertInstanceOf(
            NamespaceTranscriber::class,
            $this->buildTranscriber()->transcribe($cd, $kubeClient, $promise)
        );
    }
}