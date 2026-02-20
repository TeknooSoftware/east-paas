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

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Ingress;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\ExposingInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberInterface;
use Teknoo\Kubernetes\Client as KubernetesClient;
use Teknoo\Kubernetes\Model\Ingress as KubeIngress;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

use function array_keys;
use function array_merge_recursive;
use function is_array;

/**
 * "Exposing transcriber" to translate CompiledDeployment's ingresses to Kubernetes Ingresses manifest.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class IngressTranscriber implements ExposingInterface
{
    use CommonTrait;

    private const string NAME_SUFFIX = '-ingress';

    private const string SECRET_SUFFIX = '-secret';

    /**
     * @var callable
     */
    private $backendProtocolAnnotationMapper = null;

    /**
     * @param array<string, mixed> $defaultIngressAnnotations
     */
    public function __construct(
        private readonly ?string $defaultIngressClass,
        private readonly ?string $defaultIngressService,
        private readonly ?int $defaultIngressPort,
        private readonly array $defaultIngressAnnotations = [],
        ?callable $backendProtocolAnnotationMapper = null,
    ) {
        $this->backendProtocolAnnotationMapper = $backendProtocolAnnotationMapper;
        if (null === $this->backendProtocolAnnotationMapper) {
            $this->backendProtocolAnnotationMapper = static fn (
                ?string $provider,
                bool $isHttpsBackend,
            ): array => match ($isHttpsBackend) {
                false => [],
                true => ['nginx.ingress.kubernetes.io/backend-protocol' => 'HTTPS'],
            };
        }
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
        callable $backendProtocolAnnotationMapper,
    ): array {
        $ruleGenerator = function (string $host) use ($prefixer, $ingress): array {
            $rule = [
                'host' => $host,
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

            return $rule;
        };

        if (!empty($metaAnnotations = $ingress->getMeta()['annotations'] ?? [])) {
            if (!is_array($metaAnnotations)) {
                $metaAnnotations = [$metaAnnotations => true];
            }

            //Can not overload default annotations by default to avoid security issues)
            $defaultIngressAnnotations = array_merge_recursive($metaAnnotations, $defaultIngressAnnotations);
        }

        $hosts = [
            $ingress->getHost() => true,
        ];

        $rules = [
            $ruleGenerator($ingress->getHost()),
        ];

        foreach ($ingress->getAliases() as $alias) {
            if (!isset($hosts[$alias])) {
                $rules[] = $ruleGenerator($alias);
                $hosts[$alias] = true;
            }
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
                'rules' => $rules,
            ],
        ];

        $provider = $ingress->getProvider() ?? $defaultIngressClass;
        if (null !== $provider) {
            $specs['spec']['ingressClassName'] = $provider;
        }

        foreach ($backendProtocolAnnotationMapper($provider, $ingress->isHttpsBackend()) as $annotation => $value) {
            $specs['metadata']['annotations'][$annotation] = $value;
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
                'hosts' => array_keys($hosts),
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
        callable $backendProtocolAnnotationMapper,
    ): KubeIngress {
        return new KubeIngress(
            static::writeSpec(
                ingress: $ingress,
                namespace: $namespace,
                defaultIngressClass: $defaultIngressClass,
                defaultIngressService: $defaultIngressService,
                defaultIngressPort: $defaultIngressPort,
                defaultIngressAnnotations: $defaultIngressAnnotations,
                prefixer: $prefixer,
                backendProtocolAnnotationMapper: $backendProtocolAnnotationMapper,
            )
        );
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        KubernetesClient $client,
        PromiseInterface $promise,
        DefaultsBag $defaultsBag,
        string $namespace,
        bool $useHierarchicalNamespaces,
    ): TranscriberInterface {

        $defaultIngressClass = $this->defaultIngressClass;
        $defaultIngressService = $this->defaultIngressService;
        $defaultIngressPort = $this->defaultIngressPort;
        $defaultIngressAnnotations = $this->defaultIngressAnnotations;
        $backendProtocolAnnotationMapper = $this->backendProtocolAnnotationMapper;

        $compiledDeployment->foreachIngress(
            static function (
                Ingress $ingress,
                string $prefix,
            ) use (
                $client,
                $namespace,
                $promise,
                $defaultIngressClass,
                $defaultIngressService,
                $defaultIngressPort,
                $defaultIngressAnnotations,
                $backendProtocolAnnotationMapper,
            ): void {
                $prefixer = self::createPrefixer($prefix);
                $kubIngress = self::convertToIngress(
                    ingress: $ingress,
                    namespace: $namespace,
                    defaultIngressClass: $defaultIngressClass,
                    defaultIngressService: $defaultIngressService,
                    defaultIngressPort: $defaultIngressPort,
                    defaultIngressAnnotations: $defaultIngressAnnotations,
                    prefixer: $prefixer,
                    backendProtocolAnnotationMapper: $backendProtocolAnnotationMapper,
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
