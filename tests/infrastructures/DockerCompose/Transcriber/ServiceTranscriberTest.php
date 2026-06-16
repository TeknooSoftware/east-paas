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
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Service;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Transport;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\GenerationInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Generation;
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
        return new ServiceTranscriber('tcp', 'udp');
    }

    public function testTranscribe(): void
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachService')
            ->willReturnCallback(function (callable $callback) use ($cd): CompiledDeploymentInterface {
                //Internal service: nothing Traefik-facing
                $callback(new Service('internal-svc', 'php-pod', [8080 => 8080], Transport::Tcp, true), 'prj');
                //External HTTPS service: reached through an ingress, no Traefik router here
                $callback(new Service('web-svc', 'nginx-pod', [443 => 8443], Transport::Https, false), 'prj');
                //External raw TCP service: Traefik TCP router + service
                $callback(new Service('db-svc', 'db-pod', [5432 => 5432], Transport::Tcp, false), 'prj');
                //External raw UDP service: Traefik UDP router + service (no rule)
                $callback(new Service('dns-svc', 'dns-pod', [53 => 53], Transport::Udp, false), 'prj');

                return $cd;
            });

        $generation = new Generation('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(4))->method('success');
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            ServiceTranscriber::class,
            $this->buildTranscriber()->transcribe(
                compiledDeployment: $cd,
                generation: $generation,
                promise: $promise,
                defaultsBag: $this->createStub(DefaultsBag::class),
                namespace: 'default',
            ),
        );

        self::assertSame(
            [
                'tcp' => [
                    'routers' => [
                        'prj-db-svc-5432' => [
                            'entryPoints' => ['tcp'],
                            'service' => 'prj-db-svc-5432',
                            'rule' => 'HostSNI(`*`)',
                        ],
                    ],
                    'services' => [
                        'prj-db-svc-5432' => [
                            'loadBalancer' => [
                                'servers' => [
                                    ['address' => 'prj-db-svc:5432'],
                                ],
                            ],
                        ],
                    ],
                ],
                'udp' => [
                    'routers' => [
                        'prj-dns-svc-53' => [
                            'entryPoints' => ['udp'],
                            'service' => 'prj-dns-svc-53',
                        ],
                    ],
                    'services' => [
                        'prj-dns-svc-53' => [
                            'loadBalancer' => [
                                'servers' => [
                                    ['address' => 'prj-dns-svc:53'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $generation->getTraefikConfig(),
        );

        //Internal/HTTPS services produce no Compose changes here.
        self::assertSame([], $generation->getComposeFile());
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

        $generation = $this->createMock(GenerationInterface::class);
        $generation->expects($this->once())
            ->method('addTraefikRouter')
            ->willThrowException(new \RuntimeException('boom'));

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            generation: $generation,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );
    }
}
