<?php

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

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber;

use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Ingress;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\IngressPath;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Service;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\AccumulatorInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\ExposingInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\TranscriberInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

use function array_merge;
use function array_unique;
use function array_values;
use function implode;

/**
 * "Exposing transcriber" translating CompiledDeployment's ingresses to a Traefik v3 dynamic configuration
 * (`http` routers and services) accumulated in the Accumulator, then serialized by the driver to the
 * `<project>.yml` file dropped into Traefik's watched directory.
 *
 * One router is emitted per ingress (rule `Host(...) || Host(<alias>)...`); each declared path produces an
 * extra, higher-priority router (`Host(...) && PathPrefix(...)`). Services point their load-balancer at the
 * Compose service DNS name on the shared external network. TLS is handled per ingress (Q4): a `tlsSecret` materialises
 * the cert/key files from the matching PaaS secret (keys `tls.crt`/`tls.key`) and references them via
 * `addTlsCertificate()`; `meta.letsencrypt: true` switches the router to the configured ACME certResolver.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class IngressTranscriber implements ExposingInterface
{
    use CommonTrait;
    use ValuesCollectorTrait;

    private const string TLS_CERT_KEY = 'tls.crt';

    private const string TLS_KEY_KEY = 'tls.key';

    /**
     * @param array<string, mixed> $defaultMiddlewares
     */
    public function __construct(
        private readonly string $webEntrypoint = 'web',
        private readonly string $secureEntrypoint = 'websecure',
        private readonly ?string $defaultCertResolver = null,
        private readonly ?string $defaultServiceName = null,
        private readonly ?int $defaultServicePort = null,
        private readonly bool $httpsBackendInsecureSkipVerify = false,
        private readonly array $defaultMiddlewares = [],
    ) {
    }

    /**
     * Pre-scan the deployment's services to resolve each PaaS service name to the Compose service (pod)
     * backing it and its listen => target ports map.
     *
     * @return array<string, array{pod: string, ports: array<int, int>}> raw service name => target
     */
    private static function collectServices(CompiledDeploymentInterface $compiledDeployment): array
    {
        $services = [];

        $compiledDeployment->foreachService(
            static function (Service $service, string $prefix) use (&$services): void {
                $services[$service->getName()] = [
                    'pod' => $service->getPodName(),
                    'ports' => $service->getPorts(),
                ];
            }
        );

        return $services;
    }

    /**
     * Resolve a PaaS service name/port to the Compose DNS host (`<pod>.<network>`) and container port. An
     * unknown service (e.g. a platform-wide default service living outside the stack) is used as-is.
     *
     * @param array<string, array{pod: string, ports: array<int, int>}> $services
     * @return array{0: string, 1: int}
     */
    private static function resolveTarget(array $services, string $serviceName, int $port, string $network): array
    {
        if (!isset($services[$serviceName])) {
            return [$serviceName, $port];
        }

        $target = $services[$serviceName]['ports'][$port] ?? $port;

        return [$services[$serviceName]['pod'] . '.' . $network, $target];
    }

    /**
     * @return array<int, string>
     */
    private static function collectHosts(Ingress $ingress): array
    {
        return array_values(
            array_unique(
                array_merge([$ingress->getHost()], $ingress->getAliases()),
            ),
        );
    }

    /**
     * @param array<int, string> $hosts
     */
    private static function hostRule(array $hosts): string
    {
        $clauses = [];
        foreach ($hosts as $host) {
            $clauses[] = 'Host(`' . $host . '`)';
        }

        return implode(' || ', $clauses);
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        AccumulatorInterface $accumulator,
        PromiseInterface $promise,
        DefaultsBag $defaultsBag,
        string $namespace,
    ): TranscriberInterface {
        $secrets = self::collectSecrets($compiledDeployment);
        $services = self::collectServices($compiledDeployment);
        $projectName = $accumulator->getProjectName();
        $network = $accumulator->getNetworkName();

        $webEntrypoint = $this->webEntrypoint;
        $secureEntrypoint = $this->secureEntrypoint;
        $defaultCertResolver = $this->defaultCertResolver;
        $defaultServiceName = $this->defaultServiceName;
        $defaultServicePort = $this->defaultServicePort;
        $httpsBackendInsecureSkipVerify = $this->httpsBackendInsecureSkipVerify;
        $defaultMiddlewares = $this->defaultMiddlewares;

        $compiledDeployment->foreachIngress(
            static function (
                Ingress $ingress,
                string $prefix
            ) use (
                $accumulator,
                $promise,
                $secrets,
                $services,
                $projectName,
                $network,
                $webEntrypoint,
                $secureEntrypoint,
                $defaultCertResolver,
                $defaultServiceName,
                $defaultServicePort,
                $httpsBackendInsecureSkipVerify,
                $defaultMiddlewares,
            ): void {
                try {
                    //Traefik names are project-scoped, the project name already carries the prefix.
                    $baseName = self::sanitizeDns($projectName . '-' . $ingress->getName());

                    $hosts = self::collectHosts($ingress);
                    $hostRule = self::hostRule($hosts);

                    $meta = $ingress->getMeta();
                    $letsEncrypt = !empty($meta['letsencrypt']);
                    $hasTls = $letsEncrypt || !empty($ingress->getTlsSecret());

                    if ($hasTls) {
                        $entryPoints = [$secureEntrypoint];
                    } else {
                        $entryPoints = [$webEntrypoint];
                    }

                    $tlsBlock = null;
                    if ($letsEncrypt && null !== $defaultCertResolver) {
                        $accumulator->setCertResolver($defaultCertResolver);
                        $tlsBlock = [
                            'certResolver' => $defaultCertResolver,
                            'domains' => [
                                [
                                    'main' => $ingress->getHost(),
                                    'sans' => $ingress->getAliases(),
                                ],
                            ],
                        ];
                    } elseif (!empty($tlsSecret = $ingress->getTlsSecret())) {
                        $options = $secrets[$tlsSecret] ?? [];
                        if (isset($options[self::TLS_CERT_KEY], $options[self::TLS_KEY_KEY])) {
                            $certFile = 'certs/' . $baseName . '.crt';
                            $keyFile = 'certs/' . $baseName . '.key';

                            $accumulator
                                ->addFile($certFile, $options[self::TLS_CERT_KEY])
                                ->addFile($keyFile, $options[self::TLS_KEY_KEY])
                                ->addTlsCertificate($certFile, $keyFile);
                        }

                        $tlsBlock = [];
                    }

                    if ($ingress->isHttpsBackend()) {
                        $scheme = 'https';
                    } else {
                        $scheme = 'http';
                    }

                    $defaultServiceTraefikName = null;
                    if (!empty($ingress->getDefaultServiceName())) {
                        $defaultServiceTraefikName = $baseName . '-default';
                        [$host, $port] = self::resolveTarget(
                            $services,
                            (string) $ingress->getDefaultServiceName(),
                            (int) $ingress->getDefaultServicePort(),
                            $network,
                        );

                        $accumulator->addTraefikService(
                            'http',
                            $defaultServiceTraefikName,
                            self::buildService(
                                accumulator: $accumulator,
                                scheme: $scheme,
                                host: $host,
                                port: $port,
                                insecureSkipVerify: $ingress->isHttpsBackend()
                                    && $httpsBackendInsecureSkipVerify,
                            ),
                        );
                    } elseif (null !== $defaultServiceName && null !== $defaultServicePort) {
                        $defaultServiceTraefikName = $baseName . '-default';
                        [$host, $port] = self::resolveTarget(
                            $services,
                            $defaultServiceName,
                            $defaultServicePort,
                            $network,
                        );

                        $accumulator->addTraefikService(
                            'http',
                            $defaultServiceTraefikName,
                            self::buildService(
                                accumulator: $accumulator,
                                scheme: 'http',
                                host: $host,
                                port: $port,
                                insecureSkipVerify: false,
                            ),
                        );
                    }

                    $result = [];

                    if (null !== $defaultServiceTraefikName) {
                        $routerSpec = [
                            'rule' => $hostRule,
                            'entryPoints' => $entryPoints,
                            'service' => $defaultServiceTraefikName,
                        ];

                        if (!empty($defaultMiddlewares)) {
                            $routerSpec['middlewares'] = $defaultMiddlewares;
                        }

                        if (null !== $tlsBlock) {
                            $routerSpec['tls'] = $tlsBlock;
                        }

                        $accumulator->addTraefikRouter('http', $baseName, $routerSpec);
                        $result['http'][$baseName] = $routerSpec;
                    }

                    foreach ($ingress->getPaths() as $path) {
                        /** @var IngressPath $path */
                        $pathRouterName = self::sanitizeDns(
                            $baseName . '-' . $path->getServiceName() . '-' . $path->getServicePort(),
                        );
                        $pathServiceName = $pathRouterName;
                        [$host, $port] = self::resolveTarget(
                            $services,
                            $path->getServiceName(),
                            $path->getServicePort(),
                            $network,
                        );

                        $accumulator->addTraefikService(
                            'http',
                            $pathServiceName,
                            self::buildService(
                                accumulator: $accumulator,
                                scheme: $scheme,
                                host: $host,
                                port: $port,
                                insecureSkipVerify: $ingress->isHttpsBackend()
                                    && $httpsBackendInsecureSkipVerify,
                            ),
                        );

                        $pathRouterSpec = [
                            'rule' => '(' . $hostRule . ') && PathPrefix(`' . $path->getPath() . '`)',
                            'entryPoints' => $entryPoints,
                            'service' => $pathServiceName,
                        ];

                        if (!empty($defaultMiddlewares)) {
                            $pathRouterSpec['middlewares'] = $defaultMiddlewares;
                        }

                        if (null !== $tlsBlock) {
                            $pathRouterSpec['tls'] = $tlsBlock;
                        }

                        $accumulator->addTraefikRouter('http', $pathRouterName, $pathRouterSpec);
                        $result['http'][$pathRouterName] = $pathRouterSpec;
                    }

                    $promise->success($result);
                } catch (Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildService(
        AccumulatorInterface $accumulator,
        string $scheme,
        string $host,
        int $port,
        bool $insecureSkipVerify,
    ): array {
        $service = [
            'loadBalancer' => [
                'servers' => [
                    ['url' => $scheme . '://' . $host . ':' . $port],
                ],
            ],
        ];

        if ('https' === $scheme && $insecureSkipVerify) {
            $transportName = self::sanitizeDns($host) . '-transport';
            $accumulator->addTraefikServersTransport($transportName, ['insecureSkipVerify' => true]);
            $service['loadBalancer']['serversTransport'] = $transportName;
        }

        return $service;
    }
}
