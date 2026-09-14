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
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Service;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Transport;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Secret;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\AccumulatorInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Accumulator;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\IngressTranscriber;
use Teknoo\Recipe\Promise\PromiseInterface;

use function array_keys;
use function array_map;
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
                        'default-prj-web' => [
                            'rule' => 'Host(`demo.example.com`) || Host(`www.example.com`)',
                            'entryPoints' => ['web'],
                            'service' => 'default-prj-web-default',
                        ],
                        'default-prj-web-api-8080' => [
                            'rule' => '(Host(`demo.example.com`) || Host(`www.example.com`)) '
                                . '&& PathPrefix(`/api`)',
                            'entryPoints' => ['web'],
                            'service' => 'default-prj-web-api-8080',
                        ],
                    ],
                    'services' => [
                        'default-prj-web-default' => [
                            'loadBalancer' => [
                                'servers' => [
                                    ['url' => 'http://front:80'],
                                ],
                            ],
                        ],
                        'default-prj-web-api-8080' => [
                            'loadBalancer' => [
                                'servers' => [
                                    ['url' => 'http://api:8080'],
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

    public function testTranscribeResolvesServicesToPodsOnTheProjectNetwork(): void
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->method('foreachSecret')->willReturnCallback(
            fn (callable $callback): CompiledDeploymentInterface => $cd,
        );
        $cd->method('foreachService')->willReturnCallback(
            function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(new Service('front', 'nginx-pod', [80 => 8080], Transport::Tcp, true), 'prj');
                $callback(new Service('api', 'php-pod', [8080 => 9000, 8443 => 9443], Transport::Tcp, true), 'prj');

                return $cd;
            },
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
                        paths: [
                            new IngressPath('/api', 'api', 8080),
                            //Unknown port on a known service: used as-is
                            new IngressPath('/other', 'api', 7000),
                            //Unknown service: used as-is (e.g. a platform-wide service outside the stack)
                            new IngressPath('/ext', 'external', 3000),
                        ],
                        tlsSecret: null,
                        httpsBackend: false,
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

        $services = $generation->getTraefikConfig()['http']['services'];
        self::assertSame(
            [
                'default-prj-web-default' => 'http://nginx-pod.default-prj-private:8080',
                'default-prj-web-api-8080' => 'http://php-pod.default-prj-private:9000',
                'default-prj-web-api-7000' => 'http://php-pod.default-prj-private:7000',
                'default-prj-web-external-3000' => 'http://external:3000',
            ],
            array_map(
                static fn (array $service): string => $service['loadBalancer']['servers'][0]['url'],
                $services,
            ),
        );
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
                        'default-prj-secure' => [
                            'rule' => 'Host(`secure.example.com`)',
                            'entryPoints' => ['websecure'],
                            'service' => 'default-prj-secure-default',
                            'tls' => [],
                        ],
                    ],
                    'services' => [
                        'default-prj-secure-default' => [
                            'loadBalancer' => [
                                'servers' => [
                                    ['url' => 'https://app:8443'],
                                ],
                                'serversTransport' => 'app-transport',
                            ],
                        ],
                    ],
                    'serversTransports' => [
                        'app-transport' => ['insecureSkipVerify' => true],
                    ],
                ],
                'tls' => [
                    'certificates' => [
                        [
                            'certFile' => '/etc/traefik/certs/default-prj-secure.crt',
                            'keyFile' => '/etc/traefik/certs/default-prj-secure.key',
                        ],
                    ],
                ],
            ],
            $generation->getTraefikConfig(),
        );

        self::assertSame(
            [
                'certs/default-prj-secure.crt' => 'CERT-DATA',
                'certs/default-prj-secure.key' => 'KEY-DATA',
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
                        'default-prj-acme' => [
                            'rule' => 'Host(`acme.example.com`) || Host(`www.acme.example.com`)',
                            'entryPoints' => ['websecure'],
                            'service' => 'default-prj-acme-default',
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
                        'default-prj-acme-default' => [
                            'loadBalancer' => [
                                'servers' => [
                                    ['url' => 'http://app:80'],
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


    public function testTranscribeWithPlatformDefaultServiceMiddlewaresAndTlsOnPaths(): void
    {
        $transcriber = new IngressTranscriber(
            defaultServiceName: 'fallback',
            defaultServicePort: 8080,
            defaultMiddlewares: ['auth@file'],
        );

        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->method('foreachSecret')->willReturnCallback(
            function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(new Secret('cert', 'map', ['tls.crt' => 'CERT', 'tls.key' => 'KEY'], 'tls'), 'prj');

                return $cd;
            },
        );
        $cd->method('foreachService')->willReturnCallback(
            function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(new Service('fallback', 'front-pod', [8080 => 80], Transport::Tcp, true), 'prj');

                return $cd;
            },
        );
        $cd->expects($this->once())
            ->method('foreachIngress')
            ->willReturnCallback(function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(
                    new Ingress(
                        name: 'web',
                        host: 'demo.example.com',
                        provider: null,
                        defaultServiceName: null,
                        defaultServicePort: null,
                        paths: [new IngressPath('/api', 'api', 9000)],
                        tlsSecret: 'cert',
                        httpsBackend: false,
                    ),
                    'prj',
                );

                return $cd;
            });

        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $transcriber->transcribe(
            compiledDeployment: $cd,
            accumulator: $generation,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );

        self::assertSame(
            [
                'routers' => [
                    'default-prj-web' => [
                        'rule' => 'Host(`demo.example.com`)',
                        'entryPoints' => ['websecure'],
                        'service' => 'default-prj-web-default',
                        'middlewares' => ['auth@file'],
                        'tls' => [],
                    ],
                    'default-prj-web-api-9000' => [
                        'rule' => '(Host(`demo.example.com`)) && PathPrefix(`/api`)',
                        'entryPoints' => ['websecure'],
                        'service' => 'default-prj-web-api-9000',
                        'middlewares' => ['auth@file'],
                        'tls' => [],
                    ],
                ],
                'services' => [
                    'default-prj-web-default' => [
                        'loadBalancer' => [
                            'servers' => [['url' => 'http://front-pod.default-prj-private:80']],
                        ],
                    ],
                    'default-prj-web-api-9000' => [
                        'loadBalancer' => [
                            'servers' => [['url' => 'http://api:9000']],
                        ],
                    ],
                ],
            ],
            $generation->getTraefikConfig()['http'],
        );
    }

    public function testTranscribeWithoutAnyDefaultServiceOnlyEmitsPathRouters(): void
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
                        defaultServiceName: null,
                        defaultServicePort: null,
                        paths: [new IngressPath('/api', 'api', 9000)],
                        tlsSecret: null,
                        httpsBackend: false,
                    ),
                    'prj',
                );

                return $cd;
            });

        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');

        new IngressTranscriber()->transcribe(
            compiledDeployment: $cd,
            accumulator: $generation,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );

        self::assertSame(['default-prj-web-api-9000'], array_keys($generation->getTraefikConfig()['http']['routers']));
    }
}
