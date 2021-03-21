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
use Maclof\Kubernetes\Repositories\ServiceRepository;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Container\Expose\Service;
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\ServiceTranscriber;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\ServiceTranscriber
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
                    new Service('foo', 'foo', [80 => 8080], 'TCP', false),
                    'default_namespace'
                );
                $callback(
                    new Service('foo', 'foo', [81 => 8081], 'TCP', true),
                    'default_namespace'
                );
                return $cd;
            });


        $repoService = $this->createMock(ServiceRepository::class);

        $repoService->expects(self::exactly(2))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(false, true);

        $repoService->expects(self::exactly(2))
            ->method('create')
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
            $this->buildTranscriber()->transcribe($cd, $kubeClient, $promise)
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
                    new Service('foo', 'foo', [80 => 8080], 'TCP', false),
                    'default_namespace'
                );
                $callback(
                    new Service('foo', 'foo', [81 => 8081], 'TCP', true),
                    'default_namespace'
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
            ->method('create')
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
            $this->buildTranscriber()->transcribe($cd, $kubeClient, $promise)
        );
    }
}