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
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Ingress;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\IngressPath;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Secret;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\AccumulatorInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Accumulator;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\IngressTranscriber;
use Teknoo\Recipe\Promise\PromiseInterface;

use function base64_encode;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(IngressTranscriber::class)]
class IngressTranscriberTest extends TestCase
{
    private function buildTranscriber(): IngressTranscriber
    {
        return new IngressTranscriber(
            webEntrypoint: 'web',
            secureEntrypoint: 'websecure',
            defaultCertResolver: 'letsencrypt',
            httpsBackendInsecureSkipVerify: true,
        );
    }

    public function testTranscribePlainHttpWithPath(): void
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->method('foreachSecret')->willReturnCallback(
            fn (callable $callback): CompiledDeploymentInterface => $cd,
        );
        $cd->expects($this->once())
            ->method('foreachIngress')
            ->willReturnCallback(function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(
                    new Ingress(
                        name: 'web',
                        host: 'demo.example.com',
                        provider: null,
                        defaultServiceName: 'front',
                        defaultServicePort: 80,
                        paths: [new IngressPath('/api', 'api', 8080)],
                        tlsSecret: null,
                        httpsBackend: false,
                        meta: [],
                        aliases: ['www.example.com'],
                    ),
                    'prj',
                );

                return $cd;
            });

        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            accumulator: $generation,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );

        self::assertSame(
            [
                'http' => [
                    'routers' => [
                        'prj-web' => [
                            'rule' => 'Host(`demo.example.com`) || Host(`www.example.com`)',
                            'entryPoints' => ['web'],
                            'service' => 'prj-web-default',
                        ],
                        'prj-web-api-8080' => [
                            'rule' => '(Host(`demo.example.com`) || Host(`www.example.com`)) '
                                . '&& PathPrefix(`/api`)',
                            'entryPoints' => ['web'],
                            'service' => 'prj-web-api-8080',
                        ],
                    ],
                    'services' => [
                        'prj-web-default' => [
                            'loadBalancer' => [
                                'servers' => [
                                    ['url' => 'http://prj-front:80'],
                                ],
                            ],
                        ],
                        'prj-web-api-8080' => [
                            'loadBalancer' => [
                                'servers' => [
                                    ['url' => 'http://prj-api:8080'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $generation->getTraefikConfig(),
        );

        self::assertSame([], $generation->getFiles());
    }

    public function testTranscribeTlsFromSecret(): void
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->method('foreachSecret')->willReturnCallback(
            function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(
                    new Secret(
                        'cert',
                        'map',
                        ['tls.crt' => 'CERT-DATA', 'tls.key' => 'base64:' . base64_encode('KEY-DATA')],
                        'tls',
                    ),
                    'prj',
                );

                return $cd;
            },
        );
        $cd->expects($this->once())
            ->method('foreachIngress')
            ->willReturnCallback(function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(
                    new Ingress(
                        name: 'secure',
                        host: 'secure.example.com',
                        provider: null,
                        defaultServiceName: 'app',
                        defaultServicePort: 8443,
                        paths: [],
                        tlsSecret: 'cert',
                        httpsBackend: true,
                        meta: [],
                        aliases: [],
                    ),
                    'prj',
                );

                return $cd;
            });

        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            accumulator: $generation,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );

        self::assertSame(
            [
                'http' => [
                    'routers' => [
                        'prj-secure' => [
                            'rule' => 'Host(`secure.example.com`)',
                            'entryPoints' => ['websecure'],
                            'service' => 'prj-secure-default',
                            'tls' => [],
                        ],
                    ],
                    'services' => [
                        'prj-secure-default' => [
                            'loadBalancer' => [
                                'servers' => [
                                    ['url' => 'https://prj-app:8443'],
                                ],
                                'serversTransport' => 'prj-app-transport',
                            ],
                        ],
                    ],
                ],
                'tls' => [
                    'certificates' => [
                        [
                            'certFile' => 'certs/prj-secure.crt',
                            'keyFile' => 'certs/prj-secure.key',
                        ],
                    ],
                ],
            ],
            $generation->getTraefikConfig(),
        );

        self::assertSame(
            [
                'certs/prj-secure.crt' => 'CERT-DATA',
                'certs/prj-secure.key' => 'KEY-DATA',
            ],
            $generation->getFiles(),
        );
    }

    public function testTranscribeLetsEncrypt(): void
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->method('foreachSecret')->willReturnCallback(
            fn (callable $callback): CompiledDeploymentInterface => $cd,
        );
        $cd->expects($this->once())
            ->method('foreachIngress')
            ->willReturnCallback(function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(
                    new Ingress(
                        name: 'acme',
                        host: 'acme.example.com',
                        provider: null,
                        defaultServiceName: 'app',
                        defaultServicePort: 80,
                        paths: [],
                        tlsSecret: null,
                        httpsBackend: false,
                        meta: ['letsencrypt' => true],
                        aliases: ['www.acme.example.com'],
                    ),
                    'prj',
                );

                return $cd;
            });

        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            accumulator: $generation,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );

        self::assertSame(
            [
                'http' => [
                    'routers' => [
                        'prj-acme' => [
                            'rule' => 'Host(`acme.example.com`) || Host(`www.acme.example.com`)',
                            'entryPoints' => ['websecure'],
                            'service' => 'prj-acme-default',
                            'tls' => [
                                'certResolver' => 'letsencrypt',
                                'domains' => [
                                    [
                                        'main' => 'acme.example.com',
                                        'sans' => ['www.acme.example.com'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'services' => [
                        'prj-acme-default' => [
                            'loadBalancer' => [
                                'servers' => [
                                    ['url' => 'http://prj-app:80'],
                                ],
                            ],
                        ],
                    ],
                ],
                'tls' => [
                    'certResolver' => 'letsencrypt',
                ],
            ],
            $generation->getTraefikConfig(),
        );
    }

    public function testTranscribeFailure(): void
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->method('foreachSecret')->willReturnCallback(
            fn (callable $callback): CompiledDeploymentInterface => $cd,
        );
        $cd->expects($this->once())
            ->method('foreachIngress')
            ->willReturnCallback(function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(
                    new Ingress(
                        name: 'web',
                        host: 'demo.example.com',
                        provider: null,
                        defaultServiceName: 'front',
                        defaultServicePort: 80,
                        paths: [],
                        tlsSecret: null,
                        httpsBackend: false,
                    ),
                    'prj',
                );

                return $cd;
            });

        $generation = $this->createMock(AccumulatorInterface::class);
        $generation->expects($this->once())
            ->method('addTraefikService')
            ->willThrowException(new \RuntimeException('boom'));

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            accumulator: $generation,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );
    }
}
