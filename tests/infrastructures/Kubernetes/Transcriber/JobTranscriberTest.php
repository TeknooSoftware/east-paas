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

use DomainException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Time\SleepServiceInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Container;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheck;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheckType;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\Image;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job as CDJob;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\Planning;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\SuccessCondition;
use Teknoo\East\Paas\Compilation\CompiledDeployment\MapReference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod\RestartPolicy;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Resource;
use Teknoo\East\Paas\Compilation\CompiledDeployment\ResourceSet;
use Teknoo\East\Paas\Compilation\CompiledDeployment\SecretReference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\UpgradeStrategy;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\Reference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\MapVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\PersistentVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\SecretVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber\JobTranscriber;
use Teknoo\Kubernetes\Client as KubeClient;
use Teknoo\Kubernetes\Model\Job;
use Teknoo\Kubernetes\Repository\DeploymentRepository;
use Teknoo\Kubernetes\Repository\JobRepository;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

use function array_map;
use function strlen;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(JobTranscriber::class)]
class JobTranscriberTest extends TestCase
{
    public function buildTranscriber(): JobTranscriber
    {
        return new JobTranscriber();
    }

    public function testSetSleepService(): void
    {
        $this->assertInstanceOf(JobTranscriber::class, $this->buildTranscriber()->setSleepService($this->createStub(SleepServiceInterface::class)));
    }

    public function testRun(): void
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects($this->once())
            ->method('foreachJob')
            ->willReturnCallback(function (callable $callback) use ($cd): MockObject|Stub {
                $image1 = new Image('foo', '/foo', true, '7.4', ['foo' => 'bar']);
                $image1 = $image1->withRegistry('repository.teknoo.run');
                $image2 = new Image('bar', '/bar', true, '7.4', []);
                $image2 = $image2->withRegistry('repository.teknoo.run');

                $volume1 = new Volume('foo1', ['foo' => 'bar'], '/foo', '/mount');
                $volume2 = new Volume('bar1', ['bar' => 'foo'], '/bar', '/mount');

                $c1 = new Container(
                    'c1',
                    'foo',
                    '7.4',
                    [80],
                    ['foo' => $volume1->import('/foo')],
                    ['foo' => 'bar', 'bar' => 'foo'],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Command,
                        command: ['ps', 'aux', 'php'],
                        path: null,
                        port: null,
                        isSecure: null,
                        successThreshold: 4,
                        failureThreshold: 7,
                    ),
                    new ResourceSet(),
                );
                $c2 = new Container(
                    'c2',
                    'bar',
                    '7.4',
                    [80],
                    [
                        'bar' => $volume2->import('/bar'),
                        'data' => new PersistentVolume('data', 'bar'),
                        'vault' => new SecretVolume('vault', '/secret', 'bar'),
                        'map' => new MapVolume('map', '/map', 'bar'),
                    ],
                    [
                        'foo' => 'bar',
                        'secret' => new SecretReference('foo', 'bar'),
                        'map' => new MapReference('foo', 'bar'),
                        'secret2' => new SecretReference('foo', null, true),
                        'map2' => new MapReference('foo', null, true),
                    ],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Http,
                        command: null,
                        path: '/status',
                        port: 8080,
                        isSecure: true,
                        successThreshold: 3,
                        failureThreshold: 6,
                    ),
                    new ResourceSet([
                        new Resource('cpu', '100m', '200m'),
                        new Resource('cpu', '100m', '200m'),
                    ]),
                );
                $c3 = new Container(
                    'c3',
                    'alpine',
                    '3.16',
                    [8080],
                    [],
                    ['foo' => 'bar', 'bar' => 'foo'],
                    null,
                    new ResourceSet(),
                );
                $c4 = new Container(
                    'c4',
                    'alpine',
                    '3.16',
                    [8080],
                    [],
                    [],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Tcp,
                        command: null,
                        path: null,
                        port: 8080,
                        isSecure: null,
                        successThreshold: 1,
                        failureThreshold: 2,
                    ),
                    new ResourceSet(),
                );

                $pod1 = new Pod('p1', 1, [$c1]);
                $pod2 = new Pod(
                    'p2',
                    1,
                    [$c2, $c3, $c4],
                    upgradeStrategy: UpgradeStrategy::Recreate,
                    fsGroup: 1000,
                    requires: ['x86_64', 'avx'],
                    isStateless: true,
                    restartPolicy: RestartPolicy::OnFailure
                );
                $pod3 = new Pod(
                    'p2',
                    1,
                    [$c2, $c3, $c4],
                    upgradeStrategy: UpgradeStrategy::Recreate,
                    fsGroup: 1000,
                    requires: ['x86_64', 'avx'],
                    isStateless: false,
                );

                $job1 = new CDJob(
                    name: 'job1',
                    pods: [$pod1, $pod2],
                    completionsCount: 1,
                    isParallel: false,
                    completion: CDJob\CompletionMode::Common,
                    successCondition: null,
                    timeLimit: 10,
                    shelfLife: 20,
                    planning: CDJob\Planning::DuringDeployment,
                    planningSchedule: null,
                );

                $job2 = new CDJob(
                    name: 'job2',
                    pods: [$pod3],
                    completionsCount: 2,
                    isParallel: true,
                    completion: CDJob\CompletionMode::Indexed,
                    successCondition: new SuccessCondition(
                        successExitCode: [0],
                        failureExistCode: [1],
                        containerName: 'c1',
                    ),
                    timeLimit: 10,
                    shelfLife: 20,
                    planning: CDJob\Planning::DuringDeployment,
                    planningSchedule: null,
                );

                $job3 = new CDJob(
                    name: 'job2',
                    pods: [$pod3],
                    completionsCount: 2,
                    isParallel: true,
                    completion: CDJob\CompletionMode::Indexed,
                    successCondition: new SuccessCondition(
                        successExitCode: [0],
                        failureExistCode: [1],
                        containerName: 'c1',
                    ),
                    timeLimit: 10,
                    planning: CDJob\Planning::Scheduled,
                    planningSchedule: '*',
                );

                $callback(
                    $job1,
                    [
                        'foo' => ['7.4' => $image1],
                        'bar' => ['7.4' => $image2]
                    ],
                    [
                        'foo' => $volume1,
                        'bar' => $volume2,
                        'data' => new PersistentVolume('data', 'bar'),
                        'vault' => new SecretVolume('vault', '/secret', 'bar'),
                        'map' => new MapVolume('bar', '/bar', 'bar'),
                    ],
                    'a-prefix',
                );
                $callback($job2, [], [], 'a-prefix', );
                $callback($job3, [], [], 'a-prefix', );
                return $cd;
            });

        $dRepo = $this->createMock(DeploymentRepository::class);

        $kubeClient->expects($this->atLeastOnce())
            ->method('setNamespace')
            ->with('default_namespace');

        $kubeClient
            ->method('__call')
            ->willReturnMap([
                ['jobs', [], $dRepo],
            ]);

        $dRepo
            ->method('setFieldSelector')
            ->willReturnSelf();

        $dRepo->expects($this->exactly(3))
            ->method('first')
            ->willReturnOnConsecutiveCalls(
                null,
                new Job(['metadata' => ['name' => 'foo']]),
                null,
            );

        $dRepo->expects($this->exactly(3))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(false, true, true);

        $dRepo->expects($this->exactly(2))
            ->method('delete');

        $dRepo->expects($this->exactly(3))
            ->method('apply')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $count = 0;
        $promise->expects($this->exactly(2))
            ->method('success')
            ->willReturnCallback(
                function ($result) use (&$count, $promise): MockObject|Stub {
                    if ($count++ === 0) {
                        $this->assertEquals([['foo'],['foo']], $result);
                    } else {
                        $this->assertEquals([['foo']], $result);
                    }

                    return $promise;
                }
            );
        $promise->method('fail')->willReturnCallback(fn (Throwable $e): PromiseInterface => throw $e);

        $this->assertInstanceOf(JobTranscriber::class, $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            client: $kubeClient,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default_namespace',
            useHierarchicalNamespaces: false,
        ));
    }


    public function testRunWithOciRegistryConfigName(): void
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects($this->once())
            ->method('foreachJob')
            ->willReturnCallback(function (callable $callback) use ($cd): MockObject|Stub {
                $image1 = new Image('foo', '/foo', true, '7.4', ['foo' => 'bar']);
                $image1 = $image1->withRegistry('repository.teknoo.run');
                $image2 = new Image('bar', '/bar', true, '7.4', []);
                $image2 = $image2->withRegistry('repository.teknoo.run');

                $volume1 = new Volume('foo1', ['foo' => 'bar'], '/foo', '/mount');
                $volume2 = new Volume('bar1', ['bar' => 'foo'], '/bar', '/mount');

                $c1 = new Container(
                    'c1',
                    'foo',
                    '7.4',
                    [80],
                    ['foo' => $volume1->import('/foo')],
                    ['foo' => 'bar', 'bar' => 'foo'],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Command,
                        command: ['ps', 'aux', 'php'],
                        path: null,
                        port: null,
                        isSecure: null,
                        successThreshold: 2,
                        failureThreshold: 5,
                    ),
                    new ResourceSet([
                        new Resource('cpu', '100m', '200m'),
                        new Resource('cpu', '100m', '200m'),
                    ]),
                );
                $c2 = new Container(
                    'c2',
                    'bar',
                    '7.4',
                    [80],
                    [
                        'bar' => $volume2->import('/bar'),
                        'data' => new PersistentVolume('data', 'bar'),
                        'vault' => new SecretVolume('vault', '/secret', 'bar'),
                        'map' => new MapVolume('map', '/secret', 'bar'),
                    ],
                    [
                        'foo' => 'bar',
                        'secret' => new SecretReference('foo', 'bar'),
                        'map' => new MapReference('foo', 'bar'),
                        'secret2' => new SecretReference('foo', null, true),
                        'map2' => new MapReference('foo', null, true),
                    ],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Http,
                        command: null,
                        path: '/status',
                        port: 8080,
                        isSecure: false,
                        successThreshold: 5,
                        failureThreshold: 3,
                    ),
                    new ResourceSet(),
                );
                $c3 = new Container(
                    'c4',
                    'alpine',
                    '3.16',
                    [8080],
                    [],
                    [],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Tcp,
                        command: null,
                        path: null,
                        port: 8080,
                        isSecure: null,
                        successThreshold: 4,
                        failureThreshold: 1,
                    ),
                    new ResourceSet([
                        new Resource('cpu', '100m', '200m'),
                        new Resource('cpu', '100m', '200m'),
                    ]),
                );

                $pod1 = new Pod('p1', 1, [$c1], 'foo');
                $pod2 = new Pod('p2', 1, [$c2, $c3], fsGroup: 1000, requires: ['x86_64', 'avx'], );

                $job = new CDJob(
                    name: 'job1',
                    pods: [$pod1, $pod2],
                    completionsCount: 1,
                    isParallel: false,
                    completion: CDJob\CompletionMode::Common,
                    successCondition: null,
                    timeLimit: 10,
                    planning: CDJob\Planning::DuringDeployment,
                    planningSchedule: null,
                );

                $callback(
                    $job,
                    [
                        'foo' => ['7.4' => $image1],
                        'bar' => ['7.4' => $image2]
                    ],
                    [
                        'foo' => $volume1,
                        'bar' => $volume2,
                        'data' => new PersistentVolume('data', 'bar'),
                        'vault' => new SecretVolume('vault', '/secret', 'bar'),
                    ],
                    'a-prefix',
                );

                return $cd;
            });

        $dRepo = $this->createMock(DeploymentRepository::class);

        $kubeClient->expects($this->atLeastOnce())
            ->method('setNamespace')
            ->with('default_namespace');

        $kubeClient
            ->method('__call')
            ->willReturnMap([
                ['jobs', [], $dRepo],
            ]);

        $dRepo
            ->method('setFieldSelector')
            ->willReturnSelf();

        $dRepo->expects($this->exactly(2))
            ->method('first')
            ->willReturnOnConsecutiveCalls(
                null,
                new Job(['metadata' => ['name' => 'foo']]),
            );

        $dRepo->expects($this->exactly(2))
            ->method('apply')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(1))
            ->method('success')
            ->willReturnCallback(
                function ($result) use ($promise): MockObject|Stub {
                    $this->assertEquals([['foo'],['foo']], $result);

                    return $promise;
                }
            );
        $promise->method('fail')->willReturnCallback(fn (Throwable $e): PromiseInterface => throw $e);

        $this->assertInstanceOf(JobTranscriber::class, $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            client: $kubeClient,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default_namespace',
            useHierarchicalNamespaces: false,
        ));
    }


    public function testRunWithOciRegistryConfigNameAsReference(): void
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects($this->once())
            ->method('foreachJob')
            ->willReturnCallback(function (callable $callback) use ($cd): MockObject|Stub {
                $image1 = new Image('foo', '/foo', true, '7.4', ['foo' => 'bar']);
                $image1 = $image1->withRegistry('repository.teknoo.run');
                $image2 = new Image('bar', '/bar', true, '7.4', []);
                $image2 = $image2->withRegistry('repository.teknoo.run');

                $volume1 = new Volume('foo1', ['foo' => 'bar'], '/foo', '/mount');
                $volume2 = new Volume('bar1', ['bar' => 'foo'], '/bar', '/mount');

                $c1 = new Container(
                    'c1',
                    'foo',
                    '7.4',
                    [80],
                    ['foo' => $volume1->import('/foo')],
                    ['foo' => 'bar', 'bar' => 'foo'],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Command,
                        command: ['ps', 'aux', 'php'],
                        path: null,
                        port: null,
                        isSecure: null,
                        successThreshold: 2,
                        failureThreshold: 5,
                    ),
                    new ResourceSet([
                        new Resource('cpu', '100m', '200m'),
                        new Resource('cpu', '100m', '200m'),
                    ]),
                );
                $c2 = new Container(
                    'c2',
                    'bar',
                    '7.4',
                    [80],
                    [
                        'bar' => $volume2->import('/bar'),
                        'data' => new PersistentVolume('data', 'bar'),
                        'vault' => new SecretVolume('vault', '/secret', 'bar'),
                        'map' => new MapVolume('map', '/secret', 'bar'),
                    ],
                    [
                        'foo' => 'bar',
                        'secret' => new SecretReference('foo', 'bar'),
                        'map' => new MapReference('foo', 'bar'),
                        'secret2' => new SecretReference('foo', null, true),
                        'map2' => new MapReference('foo', null, true),
                    ],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Http,
                        command: null,
                        path: '/status',
                        port: 8080,
                        isSecure: false,
                        successThreshold: 5,
                        failureThreshold: 3,
                    ),
                    new ResourceSet(),
                );
                $c3 = new Container(
                    'c4',
                    'alpine',
                    '3.16',
                    [8080],
                    [],
                    [],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Tcp,
                        command: null,
                        path: null,
                        port: 8080,
                        isSecure: null,
                        successThreshold: 4,
                        failureThreshold: 1,
                    ),
                    new ResourceSet([
                        new Resource('cpu', '100m', '200m'),
                        new Resource('cpu', '100m', '200m'),
                    ]),
                );

                $pod1 = new Pod('p1', 1, [$c1], new Reference('oci-registry-config-name'));
                $pod2 = new Pod('p2', 1, [$c2, $c3], fsGroup: 1000, requires: ['x86_64', 'avx'], );

                $job = new CDJob(
                    name: 'job1',
                    pods: [$pod1, $pod2],
                    completionsCount: 1,
                    isParallel: false,
                    completion: CDJob\CompletionMode::Common,
                    successCondition: null,
                    timeLimit: 10,
                    planning: CDJob\Planning::DuringDeployment,
                    planningSchedule: null,
                );

                $callback(
                    $job,
                    [
                        'foo' => ['7.4' => $image1],
                        'bar' => ['7.4' => $image2]
                    ],
                    [
                        'foo' => $volume1,
                        'bar' => $volume2,
                        'data' => new PersistentVolume('data', 'bar'),
                        'vault' => new SecretVolume('vault', '/secret', 'bar'),
                    ],
                    'a-prefix',
                );

                return $cd;
            });

        $dRepo = $this->createMock(DeploymentRepository::class);

        $kubeClient->expects($this->atLeastOnce())
            ->method('setNamespace')
            ->with('default_namespace');

        $kubeClient
            ->method('__call')
            ->willReturnMap([
                ['jobs', [], $dRepo],
            ]);

        $dRepo
            ->method('setFieldSelector')
            ->willReturnSelf();

        $dRepo->expects($this->exactly(2))
            ->method('first')
            ->willReturnOnConsecutiveCalls(
                null,
                new Job(['metadata' => ['name' => 'foo']]),
            );

        $dRepo->expects($this->exactly(2))
            ->method('apply')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(1))
            ->method('success')
            ->willReturnCallback(
                function ($result) use ($promise): MockObject|Stub {
                    $this->assertEquals([['foo'],['foo']], $result);

                    return $promise;
                }
            );
        $promise->method('fail')->willReturnCallback(fn (Throwable $e): PromiseInterface => throw $e);

        $this->assertInstanceOf(JobTranscriber::class, $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            client: $kubeClient,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default_namespace',
            useHierarchicalNamespaces: false,
        ));
    }

    public function testErrorOnFetching(): void
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects($this->once())
            ->method('foreachJob')
            ->willReturnCallback(function (callable $callback) use ($cd): MockObject|Stub {
                $image1 = new Image('foo', '/foo', true, '7.4', ['foo' => 'bar']);
                $image1 = $image1->withRegistry('repository.teknoo.run');
                $image2 = new Image('bar', '/bar', true, '7.4', []);
                $image2 = $image2->withRegistry('repository.teknoo.run');

                $volume1 = new Volume('foo1', ['foo' => 'bar'], '/foo', '/mount');
                $volume2 = new Volume('bar1', ['bar' => 'foo'], '/bar', '/mount');

                $c1 = new Container(
                    'c1',
                    'foo',
                    '7.4',
                    [80],
                    ['foo' => $volume1->import('/foo')],
                    [],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Command,
                        command: ['ps', 'aux', 'php'],
                        path: null,
                        port: null,
                        isSecure: null,
                        successThreshold: 12,
                        failureThreshold: 23,
                    ),
                    new ResourceSet(),
                );
                $c2 = new Container(
                    'c2',
                    'bar',
                    '7.4',
                    [80],
                    ['bar' => $volume2->import('/bar')],
                    [],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Http,
                        command: null,
                        path: '/status',
                        port: 8080,
                        isSecure: true,
                        successThreshold: 1,
                        failureThreshold: 2,
                    ),
                    new ResourceSet(),
                );
                $c3 = new Container(
                    'c4',
                    'alpine',
                    '3.16',
                    [8080],
                    [],
                    [],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Tcp,
                        command: null,
                        path: null,
                        port: 8080,
                        isSecure: null,
                        successThreshold: 1,
                        failureThreshold: 2,
                    ),
                    new ResourceSet(),
                );

                $pod1 = new Pod('p1', 1, [$c1]);
                $pod2 = new Pod('p2', 1, [$c2, $c3], upgradeStrategy: UpgradeStrategy::RollingUpgrade, fsGroup: 1000);

                $job = new CDJob(
                    name: 'job1',
                    pods: [$pod1, $pod2],
                    completionsCount: 1,
                    isParallel: false,
                    completion: CDJob\CompletionMode::Common,
                    successCondition: null,
                    timeLimit: 10,
                    planning: CDJob\Planning::DuringDeployment,
                    planningSchedule: null,
                );

                $callback(
                    $job,
                    [
                        'foo' => ['7.4' => $image1],
                        'bar' => ['7.4' => $image2]
                    ],
                    [
                        'foo' => $volume1,
                        'bar' => $volume2,
                    ],
                    'a-prefix',
                );

                $callback(
                    $job,
                    [
                        'foo' => ['7.4' => $image1],
                        'bar' => ['7.4' => $image2]
                    ],
                    [
                        'foo' => $volume1,
                        'bar' => $volume2,
                    ],
                    'a-prefix',
                );
                return $cd;
            });

        $repo = $this->createMock(DeploymentRepository::class);
        $kubeClient
            ->expects($this->atLeastOnce())
            ->method('__call')
            ->with('jobs')
            ->willReturn($repo);

        $repo
            ->method('setFieldSelector')
            ->willReturnSelf();

        $call = 0;
        $repo->expects($this->exactly(3))
            ->method('first')
            ->willReturnCallback(
                function () use (&$call): null {
                    if (0 === $call) {
                        ++$call;
                        throw new DomainException('foo');
                    }

                    return null;
                },
            );

        $repo->expects($this->exactly(2))
            ->method('apply')
            ->willReturn(['foo']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(1))
            ->method('success')
            ->willReturnCallback(
                function ($result) use ($promise): MockObject|Stub {
                    $this->assertEquals([['foo'],['foo']], $result);

                    return $promise;
                }
            );
        $promise->expects($this->once())->method('fail');

        $this->assertInstanceOf(JobTranscriber::class, $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            client: $kubeClient,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default_namespace',
            useHierarchicalNamespaces: false,
        ));
    }

    public function testError(): void
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $cd->expects($this->once())
            ->method('foreachJob')
            ->willReturnCallback(function (callable $callback) use ($cd): MockObject|Stub {
                $image1 = new Image('foo', '/foo', true, '7.4', ['foo' => 'bar']);
                $image1 = $image1->withRegistry('repository.teknoo.run');
                $image2 = new Image('bar', '/bar', true, '7.4', []);
                $image2 = $image2->withRegistry('repository.teknoo.run');

                $volume1 = new Volume('foo1', ['foo' => 'bar'], '/foo', '/mount');
                $volume2 = new Volume('bar1', ['bar' => 'foo'], '/bar', '/mount');

                $c1 = new Container(
                    'c1',
                    'foo',
                    '7.4',
                    [80],
                    ['foo' => $volume1->import('/foo')],
                    [],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Command,
                        command: ['ps', 'aux', 'php'],
                        path: null,
                        port: null,
                        isSecure: null,
                        successThreshold: 1,
                        failureThreshold: 2,
                    ),
                    new ResourceSet(),
                );
                $c2 = new Container(
                    'c2',
                    'bar',
                    '7.4',
                    [80],
                    ['bar' => $volume2->import('/bar')],
                    [],
                    new HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: HealthCheckType::Http,
                        command: null,
                        path: '/status',
                        port: 8080,
                        isSecure: false,
                        successThreshold: 1,
                        failureThreshold: 2,
                    ),
                    new ResourceSet(),
                );

                $pod1 = new Pod('p1', 1, [$c1]);
                $pod2 = new Pod('p2', 1, [$c2], fsGroup: 1000, requires: ['x86_64', 'avx'], );

                $job = new CDJob(
                    name: 'job1',
                    pods: [$pod1, $pod2],
                    completionsCount: 1,
                    isParallel: false,
                    completion: CDJob\CompletionMode::Common,
                    successCondition: null,
                    timeLimit: 10,
                    planning: CDJob\Planning::DuringDeployment,
                    planningSchedule: null,
                );

                $callback(
                    $job,
                    [
                        'foo' => ['7.4' => $image1],
                        'bar' => ['7.4' => $image2]
                    ],
                    [
                        'foo' => $volume1,
                        'bar' => $volume2,
                    ],
                    'a-prefix',
                );

                $callback(
                    $job,
                    [
                        'foo' => ['7.4' => $image1],
                        'bar' => ['7.4' => $image2]
                    ],
                    [
                        'foo' => $volume1,
                        'bar' => $volume2,
                    ],
                    'a-prefix',
                );

                return $cd;
            });

        $repo = $this->createMock(DeploymentRepository::class);
        $kubeClient
            ->expects($this->atLeastOnce())
            ->method('__call')
            ->with('jobs')
            ->willReturn($repo);

        $repo
            ->method('setFieldSelector')
            ->willReturnSelf();

        $repo->expects($this->exactly(3))
            ->method('first')
            ->willReturnOnConsecutiveCalls(
                null,
                null,
                new Job(['metadata' => ['name' => 'foo']]),
            );

        $call = 0;
        $repo->expects($this->exactly(3))
            ->method('apply')
            ->willReturnCallback(
                function () use (&$call): array {
                    if (0 === $call) {
                        ++$call;
                        throw new DomainException('foo');
                    }

                    return ['foo'];
                },
            );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(1))
            ->method('success')
            ->willReturnCallback(
                function ($result) use ($promise): MockObject|Stub {
                    $this->assertEquals([['foo'],['foo']], $result);

                    return $promise;
                }
            );
        $promise->expects($this->once())->method('fail');

        $this->assertInstanceOf(JobTranscriber::class, $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            client: $kubeClient,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default_namespace',
            useHierarchicalNamespaces: false,
        ));
    }

    private function buildCDForVersionLevel(CompiledDeploymentInterface $cd): void
    {
        $cd->expects($this->once())
            ->method('foreachJob')
            ->willReturnCallback(function (callable $callback) use ($cd): MockObject {
                $image1 = new Image('foo', '/foo', true, '7.4', []);
                $image1 = $image1->withRegistry('repository.teknoo.run');
                $volume1 = new Volume('foo1', ['foo' => 'bar'], '/foo', '/mount');
                $volume1 = $volume1->withRegistry('repository.teknoo.run');

                $c1 = new Container(
                    'c1',
                    'foo',
                    '7.4',
                    [80],
                    ['foo' => $volume1->import('/foo')],
                    [],
                    null,
                    new ResourceSet(),
                );

                $pod1 = new Pod('p1', 1, [$c1]);

                $job1 = new CDJob(
                    name: 'job1',
                    pods: [$pod1],
                    completionsCount: 1,
                    isParallel: false,
                    completion: CDJob\CompletionMode::Common,
                    successCondition: null,
                    timeLimit: 10,
                    shelfLife: 20,
                    planning: Planning::DuringDeployment,
                    planningSchedule: null,
                );

                $callback($job1, ['foo' => ['7.4' => $image1]], ['foo' => $volume1], 'a-prefix');

                return $cd;
            });
    }

    public function testRunWithKubernetes136(): void
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $this->buildCDForVersionLevel($cd);

        $kubeClient->expects($this->atLeastOnce())->method('setNamespace')->with('default_namespace');
        $repo = $this->createMock(DeploymentRepository::class);
        $kubeClient->method('__call')->willReturnMap([['jobs', [], $repo]]);
        $repo->method('setFieldSelector')->willReturnSelf();
        $repo->method('first')->willReturn(null);
        $repo->method('exists')->willReturn(false);

        $capturedSpec = null;
        $repo->expects($this->once())
            ->method('apply')
            ->willReturnCallback(
                function (Job $model) use (&$capturedSpec): array {
                    $capturedSpec = $model->toArray()['spec']['template']['spec'];
                    return ['foo'];
                }
            );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $transcriber = new JobTranscriber('paas.east.teknoo.net', '1.36');
        $transcriber->setSleepService($this->createStub(SleepServiceInterface::class));
        $this->assertInstanceOf(JobTranscriber::class, $transcriber->transcribe(
            compiledDeployment: $cd,
            client: $kubeClient,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default_namespace',
            useHierarchicalNamespaces: false,
        ));

        $this->assertIsArray($capturedSpec);
        $this->assertFalse($capturedSpec['hostUsers']);
        $this->assertArrayNotHasKey('initContainers', $capturedSpec);
        $this->assertSame(
            [
                'name' => 'foo1-volume',
                'image' => [
                    'reference' => 'repository.teknoo.run/foo1',
                    'pullPolicy' => 'Always',
                ],
            ],
            $capturedSpec['volumes'][0],
        );
    }

    public function testRunWithKubernetes135Explicit(): void
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $this->buildCDForVersionLevel($cd);

        $kubeClient->expects($this->atLeastOnce())->method('setNamespace')->with('default_namespace');
        $repo = $this->createMock(DeploymentRepository::class);
        $kubeClient->method('__call')->willReturnMap([['jobs', [], $repo]]);
        $repo->method('setFieldSelector')->willReturnSelf();
        $repo->method('first')->willReturn(null);
        $repo->method('exists')->willReturn(false);

        $capturedSpec = null;
        $repo->expects($this->once())
            ->method('apply')
            ->willReturnCallback(
                function (Job $model) use (&$capturedSpec): array {
                    $capturedSpec = $model->toArray()['spec']['template']['spec'];
                    return ['foo'];
                }
            );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $transcriber = new JobTranscriber('paas.east.teknoo.net', '1.35');
        $transcriber->setSleepService($this->createStub(SleepServiceInterface::class));
        $this->assertInstanceOf(JobTranscriber::class, $transcriber->transcribe(
            compiledDeployment: $cd,
            client: $kubeClient,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default_namespace',
            useHierarchicalNamespaces: false,
        ));

        $this->assertIsArray($capturedSpec);
        $this->assertArrayNotHasKey('hostUsers', $capturedSpec);
        $this->assertArrayNotHasKey('initContainers', $capturedSpec);
        $this->assertSame(
            [
                'name' => 'foo1-volume',
                'image' => [
                    'reference' => 'repository.teknoo.run/foo1',
                    'pullPolicy' => 'Always',
                ],
            ],
            $capturedSpec['volumes'][0],
        );
    }

    public function testRunWithKubernetes130Explicit(): void
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $this->buildCDForVersionLevel($cd);

        $kubeClient->expects($this->atLeastOnce())->method('setNamespace')->with('default_namespace');
        $repo = $this->createMock(DeploymentRepository::class);
        $kubeClient->method('__call')->willReturnMap([['jobs', [], $repo]]);
        $repo->method('setFieldSelector')->willReturnSelf();
        $repo->method('first')->willReturn(null);
        $repo->method('exists')->willReturn(false);

        $capturedSpec = null;
        $repo->expects($this->once())
            ->method('apply')
            ->willReturnCallback(
                function (Job $model) use (&$capturedSpec): array {
                    $capturedSpec = $model->toArray()['spec']['template']['spec'];
                    return ['foo'];
                }
            );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $transcriber = new JobTranscriber('paas.east.teknoo.net', '1.30');
        $transcriber->setSleepService($this->createStub(SleepServiceInterface::class));
        $this->assertInstanceOf(JobTranscriber::class, $transcriber->transcribe(
            compiledDeployment: $cd,
            client: $kubeClient,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default_namespace',
            useHierarchicalNamespaces: false,
        ));

        $this->assertIsArray($capturedSpec);
        $this->assertArrayNotHasKey('hostUsers', $capturedSpec);
        $this->assertArrayHasKey('initContainers', $capturedSpec);
        $this->assertSame('foo1', $capturedSpec['initContainers'][0]['name']);
        $this->assertSame(
            [
                'name' => 'foo1-volume',
                'emptyDir' => [],
            ],
            $capturedSpec['volumes'][0],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function runTranscribeWithClusterVersion(
        string $configuredVersion,
        mixed $clusterVersionPayload,
    ): array {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $this->buildCDForVersionLevel($cd);

        $kubeClient->expects($this->atLeastOnce())->method('setNamespace')->with('default_namespace');
        $kubeClient->method('version')->willReturn($clusterVersionPayload);

        $repo = $this->createMock(DeploymentRepository::class);
        $kubeClient->method('__call')->willReturnMap([['jobs', [], $repo]]);
        $repo->method('setFieldSelector')->willReturnSelf();
        $repo->method('first')->willReturn(null);
        $repo->method('exists')->willReturn(false);

        $capturedSpec = null;
        $repo->expects($this->once())
            ->method('apply')
            ->willReturnCallback(
                function (Job $model) use (&$capturedSpec): array {
                    $capturedSpec = $model->toArray()['spec']['template']['spec'];
                    return ['foo'];
                }
            );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $transcriber = new JobTranscriber('paas.east.teknoo.net', $configuredVersion);
        $transcriber->setSleepService($this->createStub(SleepServiceInterface::class));
        $this->assertInstanceOf(JobTranscriber::class, $transcriber->transcribe(
            compiledDeployment: $cd,
            client: $kubeClient,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default_namespace',
            useHierarchicalNamespaces: false,
        ));

        $this->assertIsArray($capturedSpec);
        return $capturedSpec;
    }

    public function testValidateVersionClampsToLowerCluster(): void
    {
        $capturedSpec = $this->runTranscribeWithClusterVersion('1.36', ['gitVersion' => 'v1.30.5']);

        $this->assertArrayNotHasKey('hostUsers', $capturedSpec);
        $this->assertArrayHasKey('initContainers', $capturedSpec);
    }

    public function testValidateVersionKeepsConfiguredWhenClusterHigher(): void
    {
        $capturedSpec = $this->runTranscribeWithClusterVersion('1.36', ['gitVersion' => 'v1.40.0']);

        $this->assertFalse($capturedSpec['hostUsers']);
        $this->assertArrayNotHasKey('initContainers', $capturedSpec);
    }

    public function testValidateVersionFallbackOnMissingGitVersion(): void
    {
        $capturedSpec = $this->runTranscribeWithClusterVersion('1.36', []);

        $this->assertFalse($capturedSpec['hostUsers']);
        $this->assertArrayNotHasKey('initContainers', $capturedSpec);
    }

    public function testValidateVersionFallbackOnGarbageGitVersion(): void
    {
        $capturedSpec = $this->runTranscribeWithClusterVersion('1.36', ['gitVersion' => 'garbage']);

        $this->assertFalse($capturedSpec['hostUsers']);
        $this->assertArrayNotHasKey('initContainers', $capturedSpec);
    }

    /**
     * @return array{0: list<array<string, string>>, 1: array<string, mixed>, 2: list<string>, 3: list<string>} field
     *     selectors used to find the previous Job, the applied Job, names passed to `exists()` and names of the
     *     deleted Jobs
     */
    private function runWithJobName(string $jobName, string $podName, string $prefix, bool $exists = false): array
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $cd = $this->createMock(CompiledDeploymentInterface::class);

        $job = new CDJob(
            name: $jobName,
            pods: [
                new Pod($podName, 1, [new Container('php', 'registry.hub.docker.com/library/php', '8.4')]),
            ],
        );

        $cd->expects($this->once())
            ->method('foreachJob')
            ->willReturnCallback(function (callable $callback) use ($cd, $job, $prefix): MockObject {
                $callback($job, [], [], $prefix);

                return $cd;
            });

        $selectors = [];
        $manifest = [];
        $repo = $this->createMock(DeploymentRepository::class);
        $kubeClient->expects($this->atLeastOnce())->method('setNamespace')->with('default_namespace');
        $kubeClient->method('__call')->willReturnMap([['jobs', [], $repo]]);
        $repo->expects($this->once())
            ->method('setFieldSelector')
            ->willReturnCallback(
                function (array $selector) use (&$selectors, $repo): DeploymentRepository {
                    $selectors[] = $selector;
                    return $repo;
                }
            );
        $repo->method('first')->willReturn(null);
        $existsNames = [];
        $repo->expects($this->once())
            ->method('exists')
            ->willReturnCallback(
                function (string $name) use (&$existsNames, $exists): bool {
                    $existsNames[] = $name;
                    return $exists;
                }
            );
        $deletedNames = [];
        $repo->expects($exists ? $this->once() : $this->never())
            ->method('delete')
            ->willReturnCallback(
                function (Job $model) use (&$deletedNames): array {
                    $deletedNames[] = $model->getMetadata('name');
                    return [];
                }
            );
        $repo->expects($this->once())
            ->method('apply')
            ->willReturnCallback(
                function (Job $model) use (&$manifest): array {
                    $manifest = $model->toArray();
                    return ['foo'];
                }
            );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $transcriber = new JobTranscriber();
        $transcriber->setSleepService($this->createStub(SleepServiceInterface::class));
        $transcriber->transcribe(
            compiledDeployment: $cd,
            client: $kubeClient,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default_namespace',
            useHierarchicalNamespaces: false,
        );

        return [$selectors, $manifest, $existsNames, $deletedNames];
    }

    public function testRunWithShortNameKeepsTheLabelAndFindsThePreviousJobByItsName(): void
    {
        [$selectors, $manifest] = $this->runWithJobName('migrations', 'php-pods', 'a-prefix');

        $this->assertSame([['metadata.name' => 'a-prefix-migrations-php-pods-job']], $selectors);
        $this->assertSame('a-prefix-migrations-php-pods-job', $manifest['metadata']['name']);
        $this->assertSame(['name' => 'a-prefix-migrations'], $manifest['metadata']['labels']);
    }

    public function testRunWithLongPrefixBoundsTheLabelAndFindsThePreviousJobByItsName(): void
    {
        //67 characters, Kubernetes limits the value of a label to 63 characters
        [$selectors, $manifest] = $this->runWithJobName(
            'database-migrations',
            'php-pods',
            'a-customer-project-deployed-on-a-shared-cluster',
        );

        //The previous Job is selected on its name, not on a label, this name being bounded to 63 characters, with its
        //suffix, like the name of the applied Job
        $this->assertSame(
            [['metadata.name' => 'a-customer-project-deployed-on-a-shared-cluster-datab-f8ec7-job']],
            $selectors,
        );
        $this->assertSame($selectors[0]['metadata.name'], $manifest['metadata']['name']);
        $this->assertSame(
            ['name' => 'a-customer-project-deployed-on-a-shared-cluster-database-0fe2e'],
            $manifest['metadata']['labels'],
        );
        $this->assertLessThanOrEqual(63, strlen($manifest['metadata']['labels']['name']));
    }

    private function buildJobWithPods(string $jobName, string ...$podNames): CDJob
    {
        return new CDJob(
            name: $jobName,
            pods: array_map(
                static fn (string $podName): Pod => new Pod(
                    $podName,
                    1,
                    [new Container('php', 'registry.hub.docker.com/library/php', '8.4')],
                ),
                $podNames,
            ),
        );
    }

    /**
     * Deploys the job with the real repository of the client, on a Kubernetes API keeping its objects between
     * deployments
     *
     * @return array<string, string> versions of the applied Jobs, by names
     */
    private function deployOn(InMemoryKubernetesApi $api, CDJob $job, string $prefix = 'a-prefix'): array
    {
        $kubeClient = $this->createMock(KubeClient::class);
        $kubeClient->expects($this->atLeastOnce())->method('setNamespace')->with('default_namespace');
        $kubeClient->method('__call')->willReturnMap([['jobs', [], new JobRepository($kubeClient)]]);
        $kubeClient->method('sendRequest')->willReturnCallback($api);

        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachJob')
            ->willReturnCallback(function (callable $callback) use ($cd, $job, $prefix): MockObject {
                $callback($job, [], [], $prefix);

                return $cd;
            });

        $versions = [];
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->willReturnCallback(
                function (array $results) use (&$versions, $promise): PromiseInterface {
                    foreach ($results as $result) {
                        $versions[$result['metadata']['name']] =
                            $result['metadata']['annotations']['teknoo.east.paas.version'];
                    }

                    return $promise;
                }
            );
        $promise->method('fail')->willReturnCallback(fn (Throwable $e): PromiseInterface => throw $e);

        $transcriber = new JobTranscriber();
        $transcriber->setSleepService($this->createStub(SleepServiceInterface::class));
        $transcriber->transcribe(
            compiledDeployment: $cd,
            client: $kubeClient,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default_namespace',
            useHierarchicalNamespaces: false,
        );

        return $versions;
    }

    public function testFirstDeploymentOfAJobIsTheVersionOne(): void
    {
        $this->assertSame(
            ['a-prefix-migrations-php-pods-job' => 'v1'],
            $this->deployOn(new InMemoryKubernetesApi(), $this->buildJobWithPods('migrations', 'php-pods')),
        );
    }

    public function testNextDeploymentsOfAJobFindThePreviousJob(): void
    {
        $api = new InMemoryKubernetesApi();
        $job = $this->buildJobWithPods('migrations', 'php-pods');

        $this->assertSame(['a-prefix-migrations-php-pods-job' => 'v1'], $this->deployOn($api, $job));
        $this->assertSame(['a-prefix-migrations-php-pods-job' => 'v2'], $this->deployOn($api, $job));
        $this->assertSame(['a-prefix-migrations-php-pods-job' => 'v3'], $this->deployOn($api, $job));

        //The labels of the Job are unchanged
        $this->assertSame(
            ['name' => 'a-prefix-migrations'],
            $api->get('a-prefix-migrations-php-pods-job')['metadata']['labels'],
        );
    }

    public function testPodsOfAJobDoNotSeeTheVersionOfEachOther(): void
    {
        //Jobs of previous deployments, all the pods of a same job have the same label `name`
        $api = new InMemoryKubernetesApi();
        foreach (['node-pods' => 'v5', 'php-pods' => 'v2'] as $podName => $version) {
            $api->add([
                'metadata' => [
                    'name' => "a-prefix-migrations-{$podName}-job",
                    'labels' => ['name' => 'a-prefix-migrations'],
                    'annotations' => ['teknoo.east.paas.version' => $version],
                ],
            ]);
        }

        $this->assertSame(
            [
                'a-prefix-migrations-php-pods-job' => 'v3',
                'a-prefix-migrations-node-pods-job' => 'v6',
            ],
            $this->deployOn($api, $this->buildJobWithPods('migrations', 'php-pods', 'node-pods')),
        );
    }

    public function testNextDeploymentsOfAJobWithALongNameFindThePreviousJob(): void
    {
        $api = new InMemoryKubernetesApi();
        $job = $this->buildJobWithPods('database-migrations', 'php-pods');
        $prefix = 'a-customer-project-deployed-on-a-shared-cluster';
        //80 characters with the `-job` suffix, the Job is applied, and found at the next deployment, with its name
        //bounded to 63 characters
        $name = 'a-customer-project-deployed-on-a-shared-cluster-datab-f8ec7-job';

        $this->assertSame([$name => 'v1'], $this->deployOn($api, $job, $prefix));
        $this->assertSame([$name => 'v2'], $this->deployOn($api, $job, $prefix));
        $this->assertNull(
            $api->get('a-customer-project-deployed-on-a-shared-cluster-database-migrations-php-pods-job'),
        );

        //The label of the Job is still bounded
        $this->assertSame(
            ['name' => 'a-customer-project-deployed-on-a-shared-cluster-database-0fe2e'],
            $api->get($name)['metadata']['labels'],
        );
    }

    public function testRunWithShortNameKeepsTheName(): void
    {
        [, $manifest, $existsNames, $deletedNames] = $this->runWithJobName(
            jobName: 'migrations',
            podName: 'php-pods',
            prefix: 'a-prefix',
            exists: true,
        );

        $this->assertSame('a-prefix-migrations-php-pods-job', $manifest['metadata']['name']);
        $this->assertSame(['a-prefix-migrations-php-pods-job'], $existsNames);
        $this->assertSame(['a-prefix-migrations-php-pods-job'], $deletedNames);
    }

    public function testRunWithNameOf63CharactersKeepsTheName(): void
    {
        //59 characters before the `-job` suffix, the 63 characters accepted by Kubernetes
        [$selectors, $manifest, $existsNames] = $this->runWithJobName(
            jobName: 'init',
            podName: 'phppod',
            prefix: 'a-customer-project-deployed-on-a-shared-cluster',
        );

        $name = 'a-customer-project-deployed-on-a-shared-cluster-init-phppod-job';
        $this->assertSame(63, strlen($name));
        $this->assertSame($name, $manifest['metadata']['name']);
        $this->assertSame([$name], $existsNames);
        $this->assertSame([['metadata.name' => $name]], $selectors);
    }

    public function testRunWithLongNameBoundsTheName(): void
    {
        //80 characters with the `-job` suffix. Kubernetes copies the name of a Job in the labels `job-name` and
        //`batch.kubernetes.io/job-name` of its pods, limited to 63 characters
        [$selectors, $manifest, $existsNames, $deletedNames] = $this->runWithJobName(
            jobName: 'database-migrations',
            podName: 'php-pods',
            prefix: 'a-customer-project-deployed-on-a-shared-cluster',
            exists: true,
        );

        $name = 'a-customer-project-deployed-on-a-shared-cluster-datab-f8ec7-job';
        $this->assertSame(63, strlen($name));
        $this->assertSame($name, $manifest['metadata']['name']);
        //The previous Job is found, for its version and before its deletion, and deleted, with the same bounded name
        $this->assertSame([['metadata.name' => $name]], $selectors);
        $this->assertSame([$name], $existsNames);
        $this->assertSame([$name], $deletedNames);

        //A name of 64 characters is bounded too
        [, $manifest] = $this->runWithJobName(
            jobName: 'init',
            podName: 'phppods',
            prefix: 'a-customer-project-deployed-on-a-shared-cluster',
        );
        $this->assertSame(
            'a-customer-project-deployed-on-a-shared-cluster-init-312cc-job',
            $manifest['metadata']['name'],
        );

        //Stable between deployments
        [, $manifest] = $this->runWithJobName(
            jobName: 'database-migrations',
            podName: 'php-pods',
            prefix: 'a-customer-project-deployed-on-a-shared-cluster',
        );
        $this->assertSame($name, $manifest['metadata']['name']);
    }
}
