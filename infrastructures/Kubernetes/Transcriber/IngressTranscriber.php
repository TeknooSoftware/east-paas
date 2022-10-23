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

use Maclof\Kubernetes\Client as KubernetesClient;
use Maclof\Kubernetes\Models\Ingress as KubeIngress;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Ingress;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\ExposingInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberInterface;
use Throwable;

/**
 * Exposing Transcriber to translate CompiledDeployment's ingresses to Kubernetes Ingresses manifest.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class IngressTranscriber implements ExposingInterface
{
    private const NAME_SUFFIX = '-ingress';

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
                        'name' => $ingress->getDefaultServiceName() . ServiceTranscriber::NAME_SUFFIX,
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
                        'name' => $path->getServiceName() . ServiceTranscriber::NAME_SUFFIX,
                        'port' => [
                            'number' => $path->getServicePort(),
                        ],
                    ],
                ]
            ];
        }


        $specs = [
            'metadata' => [
                'name' => $ingress->getName() . self::NAME_SUFFIX,
                'namespace' => $namespace,
                'labels' => [
                    'name' => $ingress->getName(),
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

        if (null !== $defaultIngressService && null !== $defaultIngressPort) {
            $specs['spec']['defaultBackend']['service'] = [
                'name' => $defaultIngressService,
                'port' => [
                    'number' => $defaultIngressPort,
                ],
            ];
        }

        if (!empty($ingress->getTlsSecret())) {
            $specs['spec']['tls'][] = [
                'hosts' => [$ingress->getHost()],
                'secretName' => $ingress->getTlsSecret(),
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
    ): KubeIngress {
        return new KubeIngress(
            self::writeSpec(
                $ingress,
                $namespace,
                $defaultIngressClass,
                $defaultIngressService,
                $defaultIngressPort,
                $defaultIngressAnnotations,
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
            ) use (
                $client,
                $promise,
                $defaultIngressClass,
                $defaultIngressService,
                $defaultIngressPort,
                $defaultIngressAnnotations,
            ) {
                $kubIngress = self::convertToIngress(
                    $ingress,
                    $namespace,
                    $defaultIngressClass,
                    $defaultIngressService,
                    $defaultIngressPort,
                    $defaultIngressAnnotations,
                );

                try {
                    if (!empty($namespace)) {
                        $client->setNamespace($namespace);
                    }

                    $ingressRepository = $client->ingresses();
                    $name = $kubIngress->getMetadata('name') ?? $ingress->getName() . self::NAME_SUFFIX;
                    if ($ingressRepository->exists($name)) {
                        $result = $ingressRepository->update($kubIngress);
                    } else {
                        $result = $ingressRepository->create($kubIngress);
                    }

                    $promise->success($result);
                } catch (Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
