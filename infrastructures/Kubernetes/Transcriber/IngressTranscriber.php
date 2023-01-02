<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Teknoo\Kubernetes\Client as KubernetesClient;
use Teknoo\Kubernetes\Model\Ingress as KubeIngress;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Ingress;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\ExposingInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberInterface;
use Throwable;

/**
 * "Exposing transcriber" to translate CompiledDeployment's ingresses to Kubernetes Ingresses manifest.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class IngressTranscriber implements ExposingInterface
{
    use CommonTrait;

    private const NAME_SUFFIX = '-ingress';
    private const SECRET_SUFFIX = '-secret';

    /**
     * @param array<string, mixed> $defaultIngressAnnotations
     */
    public function __construct(
        private readonly ?string $defaultIngressClass,
        private readonly ?string $defaultIngressService,
        private readonly ?int $defaultIngressPort,
        private readonly array $defaultIngressAnnotations = [],
    ) {
    }

    /**
     * @param array<string, mixed> $defaultIngressAnnotations
     * @return array<string, mixed>
     */
    protected static function writeSpec(
        Ingress $ingress,
        string $namespace,
        ?string $defaultIngressClass,
        ?string $defaultIngressService,
        ?int $defaultIngressPort,
        array $defaultIngressAnnotations,
        callable $prefixer,
    ): array {
        $rule = [
            'host' => $ingress->getHost(),
            'http' => [
                'paths' => [],
            ],
        ];

        if (!empty($ingress->getDefaultServiceName())) {
            $rule['http']['paths'][] = [
                'path' => '/',
                'pathType' => 'Prefix',
                'backend' => [
                    'service' => [
                        'name' => $prefixer($ingress->getDefaultServiceName()),
                        'port' => [
                            'number' => $ingress->getDefaultServicePort(),
                        ],
                    ],
                ]
            ];
        }

        foreach ($ingress->getPaths() as $path) {
            $rule['http']['paths'][] = [
                'path' => $path->getPath(),
                'pathType' => 'Prefix',
                'backend' => [
                    'service' => [
                        'name' => $prefixer($path->getServiceName()),
                        'port' => [
                            'number' => $path->getServicePort(),
                        ],
                    ],
                ]
            ];
        }


        $specs = [
            'metadata' => [
                'name' => $prefixer($ingress->getName() . self::NAME_SUFFIX),
                'namespace' => $namespace,
                'labels' => [
                    'name' => $prefixer($ingress->getName()),
                ],
                'annotations' => $defaultIngressAnnotations,
            ],
            'spec' => [
                'rules' => [$rule],
            ],
        ];

        if (null !== $defaultIngressClass || null !== $ingress->getProvider()) {
            $provider = $ingress->getProvider() ?? $defaultIngressClass;
            $specs['metadata']['annotations']['kubernetes.io/ingress.class'] = $provider;
        }

        if ($ingress->isHttpsBackend()) {
            $specs['metadata']['annotations']['nginx.ingress.kubernetes.io/backend-protocol'] = 'HTTPS';
        }

        if (null !== $defaultIngressService && null !== $defaultIngressPort) {
            $specs['spec']['defaultBackend']['service'] = [
                'name' => $prefixer($defaultIngressService),
                'port' => [
                    'number' => $defaultIngressPort,
                ],
            ];
        }

        if (!empty($ingress->getTlsSecret())) {
            $specs['spec']['tls'][] = [
                'hosts' => [$ingress->getHost()],
                'secretName' => $prefixer($ingress->getTlsSecret() . self::SECRET_SUFFIX),
            ];
        }

        return $specs;
    }

    /**
     * @param array<string, mixed> $defaultIngressAnnotations
     */
    private static function convertToIngress(
        Ingress $ingress,
        string $namespace,
        ?string $defaultIngressClass,
        ?string $defaultIngressService,
        ?int $defaultIngressPort,
        array $defaultIngressAnnotations,
        callable $prefixer,
    ): KubeIngress {
        return new KubeIngress(
            static::writeSpec(
                $ingress,
                $namespace,
                $defaultIngressClass,
                $defaultIngressService,
                $defaultIngressPort,
                $defaultIngressAnnotations,
                $prefixer,
            )
        );
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        KubernetesClient $client,
        PromiseInterface $promise
    ): TranscriberInterface {

        $defaultIngressClass = $this->defaultIngressClass;
        $defaultIngressService = $this->defaultIngressService;
        $defaultIngressPort = $this->defaultIngressPort;
        $defaultIngressAnnotations = $this->defaultIngressAnnotations;

        $compiledDeployment->foreachIngress(
            static function (
                Ingress $ingress,
                string $namespace,
                string $prefix,
            ) use (
                $client,
                $promise,
                $defaultIngressClass,
                $defaultIngressService,
                $defaultIngressPort,
                $defaultIngressAnnotations,
            ): void {
                $prefixer = self::createPrefixer($prefix);
                $kubIngress = self::convertToIngress(
                    $ingress,
                    $namespace,
                    $defaultIngressClass,
                    $defaultIngressService,
                    $defaultIngressPort,
                    $defaultIngressAnnotations,
                    $prefixer,
                );

                try {
                    if (!empty($namespace)) {
                        $client->setNamespace($namespace);
                    }

                    $ingressRepository = $client->ingresses();
                    $result = $ingressRepository->apply($kubIngress);

                    $result = self::cleanResult($result);

                    $promise->success($result);
                } catch (Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
