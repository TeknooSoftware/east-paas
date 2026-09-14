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

namespace Teknoo\Tests\East\Paas\Infrastructures\DockerCompose\Transcriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Container;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Service;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Transport;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Accumulator;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\AccumulatorInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\ServiceTranscriber;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ServiceTranscriber::class)]
class ServiceTranscriberTest extends TestCase
{
    private function buildTranscriber(): ServiceTranscriber
    {
        return new ServiceTranscriber();
    }

    public function testTranscribe(): void
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachService')
            ->willReturnCallback(function (callable $callback) use ($cd): CompiledDeploymentInterface {
                //Internal service: nothing host-facing
                $callback(new Service('internal-svc', 'php-pod', [8080 => 8080], Transport::Tcp, true), 'prj');
                //External HTTPS service: host port published
                $callback(new Service('web-svc', 'nginx-pod', [443 => 8443], Transport::Https, false), 'prj');
                //External raw TCP service: host port published
                $callback(new Service('db-svc', 'db-pod', [5432 => 5432], Transport::Tcp, false), 'prj');

                return $cd;
            });

        //The DeploymentTranscriber keys services by the raw pod name (no prefix), so the ports are
        //published on those same keys.
        $accumulator = new Accumulator('default-prj', 'private');
        $accumulator
            ->addService('php-pod', ['image' => 'php', 'networks' => ['default-prj-private']])
            ->addService('nginx-pod', ['image' => 'nginx', 'networks' => ['default-prj-private']])
            ->addService('db-pod', ['image' => 'postgres', 'networks' => ['default-prj-private']]);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(3))->method('success');
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            ServiceTranscriber::class,
            $this->buildTranscriber()->transcribe(
                compiledDeployment: $cd,
                accumulator: $accumulator,
                promise: $promise,
                defaultsBag: $this->createStub(DefaultsBag::class),
                namespace: 'default',
            ),
        );

        $services = $accumulator->getComposeFile()['services'];

        //Internal service: untouched, no host ports
        self::assertArrayNotHasKey('ports', $services['php-pod']);
        //External services: host ports published on the pod's Compose service
        self::assertSame(['443:8443'], $services['nginx-pod']['ports']);
        self::assertSame(['5432:5432'], $services['db-pod']['ports']);

        //No Traefik configuration is produced by this transcriber.
        self::assertSame([], $accumulator->getTraefikConfig());
    }

    public function testTranscribeUdpAndReplicatedPods(): void
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->method('foreachPod')->willReturnCallback(
            function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(new Pod('dns-pod', 1, [new Container('dns', 'registry/dns', '1', [53], [], [])]), [], [], 'prj');
                $callback(new Pod('php-pod', 2, [new Container('php', 'registry/php', '8', [9000], [], [])]), [], [], 'prj');

                return $cd;
            },
        );
        $cd->expects($this->once())
            ->method('foreachService')
            ->willReturnCallback(function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(new Service('dns-svc', 'dns-pod', [53 => 53], Transport::Udp, false), 'prj');
                //Replicated pod: a host port cannot be bound by several containers, skipped with a warning
                $callback(new Service('php-svc', 'php-pod', [9000 => 9000], Transport::Tcp, false), 'prj');

                return $cd;
            });

        $accumulator = new Accumulator('default-prj', 'private');
        $accumulator
            ->addService('dns-pod', ['image' => 'dns', 'networks' => ['default-prj-private']])
            ->addService('php-pod', ['image' => 'php', 'networks' => ['default-prj-private']]);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(2))->method('success');
        $promise->expects($this->never())->method('fail');

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            accumulator: $accumulator,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );

        $services = $accumulator->getComposeFile()['services'];
        self::assertSame(['53:53/udp'], $services['dns-pod']['ports']);
        self::assertArrayNotHasKey('ports', $services['php-pod']);
        self::assertSame(
            ['default-prj-private' => ['aliases' => ['php-svc']]],
            $services['php-pod']['networks'],
        );
        self::assertCount(1, $accumulator->getWarnings());
        self::assertStringContainsString('php-svc', $accumulator->getWarnings()[0]);
    }

    public function testTranscribeFailure(): void
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachService')
            ->willReturnCallback(function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(new Service('db-svc', 'db-pod', [5432 => 5432], Transport::Tcp, false), 'prj');

                return $cd;
            });

        $accumulator = $this->createMock(AccumulatorInterface::class);
        $accumulator->expects($this->once())
            ->method('publishPorts')
            ->willThrowException(new \RuntimeException('boom'));

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            accumulator: $accumulator,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );
    }
}
