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
use Teknoo\East\Paas\Compilation\CompiledDeployment\Secret;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\GenerationInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\ExposingInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\TranscriberInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

use function array_merge;
use function array_unique;
use function array_values;
use function base64_decode;
use function implode;
use function is_array;
use function is_scalar;
use function is_string;
use function str_starts_with;
use function strlen;
use function substr;

use const PHP_EOL;

/**
 * "Exposing transcriber" translating CompiledDeployment's ingresses to a Traefik v3 dynamic configuration
 * (`http` routers and services) accumulated in the Generation, then serialized by the driver to the
 * `<project>.yml` file dropped into Traefik's watched directory.
 *
 * One router is emitted per ingress (rule `Host(...) || Host(<alias>)...`); each declared path produces an
 * extra, higher-priority router (`Host(...) && PathPrefix(...)`). Services point their load-balancer at the
 * Compose service DNS name on the wired network. TLS is handled per ingress (Q4): a `tlsSecret` materialises
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

    private const string BASE64_PREFIX = 'base64:';

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

    private static function decode(mixed $value): string
    {
        if (is_string($value) && str_starts_with($value, self::BASE64_PREFIX)) {
            return (string) base64_decode(substr($value, strlen(self::BASE64_PREFIX)), true);
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }

    /**
     * Pre-scan the deployment's `map`-provider secrets so the per-ingress TLS lookup can materialise the
     * cert/key files from the secret referenced by `tls.secret`.
     *
     * @return array<string, array<string, string>> secret name => decoded option key => value
     */
    private static function collectSecrets(CompiledDeploymentInterface $compiledDeployment): array
    {
        $secrets = [];

        $compiledDeployment->foreachSecret(
            static function (Secret $secret, string $prefix) use (&$secrets): void {
                if ('map' !== $secret->getProvider()) {
                    return;
                }

                $options = [];
                foreach ($secret->getOptions() as $key => $value) {
                    if (is_array($value)) {
                        $value = implode(PHP_EOL, $value);
                    }

                    $options[(string) $key] = self::decode($value);
                }

                $secrets[$secret->getName()] = $options;
            }
        );

        return $secrets;
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
        GenerationInterface $generation,
        PromiseInterface $promise,
        DefaultsBag $defaultsBag,
        string $namespace,
    ): TranscriberInterface {
        $secrets = self::collectSecrets($compiledDeployment);

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
                $generation,
                $promise,
                $secrets,
                $webEntrypoint,
                $secureEntrypoint,
                $defaultCertResolver,
                $defaultServiceName,
                $defaultServicePort,
                $httpsBackendInsecureSkipVerify,
                $defaultMiddlewares,
            ): void {
                try {
                    $prefixer = self::createPrefixer($prefix);
                    $baseName = self::sanitizeDns($prefixer($ingress->getName()));

                    $hosts = self::collectHosts($ingress);
                    $hostRule = self::hostRule($hosts);

                    $meta = $ingress->getMeta();
                    $letsEncrypt = !empty($meta['letsencrypt']);
                    $hasTls = $letsEncrypt || !empty($ingress->getTlsSecret());

                    $entryPoints = [$hasTls ? $secureEntrypoint : $webEntrypoint];

                    $tlsBlock = null;
                    if ($letsEncrypt && null !== $defaultCertResolver) {
                        $generation->setCertResolver($defaultCertResolver);
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

                            $generation
                                ->addFile($certFile, $options[self::TLS_CERT_KEY])
                                ->addFile($keyFile, $options[self::TLS_KEY_KEY])
                                ->addTlsCertificate($certFile, $keyFile);
                        }

                        $tlsBlock = [];
                    }

                    $scheme = $ingress->isHttpsBackend() ? 'https' : 'http';

                    $defaultServiceTraefikName = null;
                    if (!empty($ingress->getDefaultServiceName())) {
                        $defaultServiceTraefikName = $baseName . '-default';

                        $generation->addTraefikService(
                            'http',
                            $defaultServiceTraefikName,
                            self::buildService(
                                scheme: $scheme,
                                host: $prefixer((string) $ingress->getDefaultServiceName()),
                                port: (int) $ingress->getDefaultServicePort(),
                                insecureSkipVerify: $ingress->isHttpsBackend()
                                    && $httpsBackendInsecureSkipVerify,
                            ),
                        );
                    } elseif (null !== $defaultServiceName && null !== $defaultServicePort) {
                        $defaultServiceTraefikName = $baseName . '-default';

                        $generation->addTraefikService(
                            'http',
                            $defaultServiceTraefikName,
                            self::buildService(
                                scheme: 'http',
                                host: $prefixer($defaultServiceName),
                                port: $defaultServicePort,
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

                        $generation->addTraefikRouter('http', $baseName, $routerSpec);
                        $result['http'][$baseName] = $routerSpec;
                    }

                    foreach ($ingress->getPaths() as $path) {
                        /** @var IngressPath $path */
                        $pathRouterName = self::sanitizeDns(
                            $baseName . '-' . $path->getServiceName() . '-' . $path->getServicePort(),
                        );
                        $pathServiceName = $pathRouterName;

                        $generation->addTraefikService(
                            'http',
                            $pathServiceName,
                            self::buildService(
                                scheme: $scheme,
                                host: $prefixer($path->getServiceName()),
                                port: $path->getServicePort(),
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

                        $generation->addTraefikRouter('http', $pathRouterName, $pathRouterSpec);
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
            $service['loadBalancer']['serversTransport'] = $host . '-transport';
        }

        return $service;
    }
}
