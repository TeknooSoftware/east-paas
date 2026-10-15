<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Service;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Transport;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\ServiceTranscriber;
use Teknoo\Kubernetes\Client as KubeClient;
use Teknoo\Kubernetes\Model\Service as KubeService;
use Teknoo\Kubernetes\Repository\ServiceRepository;
use Teknoo\Recipe\Promise\PromiseInterface;

use function strlen;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ServiceTranscriber::class)]
class ServiceTranscriberTest extends TestCase
{
    public function buildTranscriber(): ServiceTranscriber
    {
        return new ServiceTranscriber();
    }

    public function testRun(): void
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);


        $cd->expects($this->once())
            ->method('foreachService')
            ->willReturnCallback(function (callable $callback) use ($cd): MockObject|Stub {
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

        $repoService->expects($this->exactly(2))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(false, true);

        $repoService->expects($this->exactly(2))
            ->method('apply')
            ->willReturn(['foo']);

        $repoService->expects($this->once())
            ->method('delete')
            ->willReturn(['foo']);

        $kubeClient->expects($this->atLeastOnce())
            ->method('setNamespace')
            ->with('default_namespace');

        $kubeClient
            ->method('__call')
            ->willReturnMap([
                ['services', [], $repoService],
            ]);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(2))->method('success')->with(['foo']);
        $promise->expects($this->never())->method('fail');

        $this->assertInstanceOf(ServiceTranscriber::class, $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            client: $kubeClient,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default_namespace',
            useHierarchicalNamespaces: false,
        ));
    }

    public function testRunHttps(): void
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects($this->once())
            ->method('foreachService')
            ->willReturnCallback(function (callable $callback) use ($cd): MockObject|Stub {
                $callback(
                    new Service('foo', 'foo', [443 => 8443], Transport::Https, false),
                    'a-prefix',
                );
                return $cd;
            });

        $repoService = $this->createMock(ServiceRepository::class);

        $repoService->expects($this->once())
            ->method('exists')
            ->willReturn(false);

        $repoService->expects($this->never())
            ->method('delete');

        $appliedSpec = null;
        $repoService->expects($this->once())
            ->method('apply')
            ->willReturnCallback(function (KubeService $service) use (&$appliedSpec): array {
                $appliedSpec = $service->toArray();
                return ['foo'];
            });

        $kubeClient->expects($this->atLeastOnce())
            ->method('setNamespace')
            ->with('default_namespace');

        $kubeClient
            ->method('__call')
            ->willReturnMap([
                ['services', [], $repoService],
            ]);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success')->with(['foo']);
        $promise->expects($this->never())->method('fail');

        $this->assertInstanceOf(ServiceTranscriber::class, $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            client: $kubeClient,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default_namespace',
            useHierarchicalNamespaces: false,
        ));

        self::assertNotNull($appliedSpec);
        self::assertSame('https-foo-443', $appliedSpec['spec']['ports'][0]['name']);
        self::assertSame('TCP', $appliedSpec['spec']['ports'][0]['protocol']);
        self::assertSame(443, $appliedSpec['spec']['ports'][0]['port']);
        self::assertSame(8443, $appliedSpec['spec']['ports'][0]['targetPort']);
        self::assertSame('LoadBalancer', $appliedSpec['spec']['type']);
    }

    public function testError(): void
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachService')
            ->willReturnCallback(function (callable $callback) use ($cd): MockObject|Stub {
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
        $kubeClient
            ->expects($this->atLeastOnce())
            ->method('__call')
            ->with('services')
            ->willReturn($repo);

        $repo->expects($this->exactly(2))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(false, true);

        $counter = 0;
        $repo->expects($this->exactly(2))
            ->method('apply')
            ->willReturnCallback(function () use (&$counter): array {
                if (0 === $counter) {
                    ++$counter;
                    return ['foo'];
                }

                throw new Exception('foo');
            });

        $repo->expects($this->once())
            ->method('delete')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success')->with(['foo']);
        $promise->expects($this->once())->method('fail');

        $this->assertInstanceOf(ServiceTranscriber::class, $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            client: $kubeClient,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default_namespace',
            useHierarchicalNamespaces: false,
        ));
    }

    /**
     * @return array<string, mixed> the applied Service
     */
    private function runWithService(Service $service, string $prefix): array
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects($this->once())
            ->method('foreachService')
            ->willReturnCallback(function (callable $callback) use ($cd, $service, $prefix): MockObject {
                $callback($service, $prefix);

                return $cd;
            });

        $manifest = [];
        $repo = $this->createMock(ServiceRepository::class);
        $kubeClient->expects($this->atLeastOnce())->method('setNamespace')->with('default_namespace');
        $kubeClient->method('__call')->willReturnMap([['services', [], $repo]]);
        $repo->method('exists')->willReturn(false);
        $repo->expects($this->once())
            ->method('apply')
            ->willReturnCallback(
                function (KubeService $model) use (&$manifest): array {
                    $manifest = $model->toArray();
                    return ['foo'];
                }
            );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success')->with(['foo']);
        $promise->expects($this->never())->method('fail');

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            client: $kubeClient,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default_namespace',
            useHierarchicalNamespaces: false,
        );

        return $manifest;
    }

    public function testRunWithShortNameKeepsTheLabelAndTheSelector(): void
    {
        $manifest = $this->runWithService(
            new Service('foo', 'foo-pod', [80 => 8080], Transport::Tcp, true),
            'a-prefix',
        );

        $this->assertSame('a-prefix-foo', $manifest['metadata']['name']);
        $this->assertSame(['name' => 'a-prefix-foo'], $manifest['metadata']['labels']);
        $this->assertSame(['name' => 'a-prefix-foo-pod'], $manifest['spec']['selector']);
    }

    public function testRunWithLongPrefixBoundsTheSelectorLikeThePodLabel(): void
    {
        //65 characters, Kubernetes limits the value of a label to 63 characters. The selector must be equal to the
        //label `name` written on the pods by DeploymentTranscriber and StatefulSetsTranscriber
        $manifest = $this->runWithService(
            new Service('php-service', 'php-pods-frontend', [80 => 8080], Transport::Tcp, true),
            'a-customer-project-deployed-on-a-shared-cluster',
        );

        $this->assertSame(
            ['name' => 'a-customer-project-deployed-on-a-shared-cluster-php-service'],
            $manifest['metadata']['labels'],
        );
        $this->assertSame(
            ['name' => 'a-customer-project-deployed-on-a-shared-cluster-php-pods-2e3a9'],
            $manifest['spec']['selector'],
        );
        $this->assertLessThanOrEqual(63, strlen($manifest['spec']['selector']['name']));
    }

    public function testRunWithLongPrefixBoundsTheLabel(): void
    {
        //68 characters, Kubernetes limits the value of a label to 63 characters
        $manifest = $this->runWithService(
            new Service('php-frontend-service', 'php-pods-frontend', [80 => 8080], Transport::Tcp, true),
            'a-customer-project-deployed-on-a-shared-cluster',
        );

        $this->assertSame(
            ['name' => 'a-customer-project-deployed-on-a-shared-cluster-php-front-97cd3'],
            $manifest['metadata']['labels'],
        );
        $this->assertSame(63, strlen($manifest['metadata']['labels']['name']));
    }
}
