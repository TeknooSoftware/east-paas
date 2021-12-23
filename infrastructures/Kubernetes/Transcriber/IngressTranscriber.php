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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
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
    private const NAME_PREFIX = '-ingress';

    public function __construct(
        private ?string $defaultIngressClass,
        private ?string $defaultIngressService,
        private ?int $defaultIngressPort,
    ) {
    }

    private function convertToIngress(
        Ingress $ingress,
        string $namespace
    ): KubeIngress {
        $rule = [
            'host' => $ingress->getHost(),
        ];

        foreach ($ingress->getPaths() as $path) {
            $rule['http']['paths'][] = [
                'path' => $path->getPath(),
                'pathType' => 'Prefix',
                'backend' => [
                    'serviceName' => $path->getServiceName(),
                    'servicePort' => $path->getServicePort(),
                ]
            ];
        }

        if (!empty($ingress->getDefaultServiceName())) {
            $rule['http']['paths'][] = [
                'path' => '/',
                'pathType' => 'Prefix',
                'backend' => [
                    'serviceName' => $ingress->getDefaultServiceName(),
                    'servicePort' => $ingress->getDefaultServicePort(),
                ]
            ];
        }

        $specs = [
            'metadata' => [
                'name' => $ingress->getName() . self::NAME_PREFIX,
                'namespace' => $namespace,
                'labels' => [
                    'name' => $ingress->getName(),
                ],
            ],
            'spec' => [
                'rules' => [$rule],
            ],
        ];

        if (null !== $this->defaultIngressClass || null !== $ingress->getProvider()) {
            $provider = $ingress->getProvider() ?? $this->defaultIngressClass;
            $specs['annotations']['kubernetes.io/ingress.class'] = $provider;
        }

        if (null !== $this->defaultIngressService && null !== $this->defaultIngressPort) {
            $specs['spec']['defaultBackend'] = [
                'serviceName' => $this->defaultIngressService,
                'servicePort' => $this->defaultIngressPort,
            ];
        }

        if (!empty($ingress->getTlsSecret())) {
            $specs['spec']['tls'][] = [
                'hosts' => [$ingress->getHost()],
                'secretName' => $ingress->getTlsSecret(),
            ];
        }

        return new KubeIngress($specs);
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        KubernetesClient $client,
        PromiseInterface $promise
    ): TranscriberInterface {
        $compiledDeployment->foreachIngress(
            function (Ingress $ingress, string $namespace) use ($client, $promise) {
                $kubIngress = $this->convertToIngress($ingress, $namespace);

                try {
                    if (!empty($namespace)) {
                        $client->setNamespace($namespace);
                    }

                    $ingressRepository = $client->ingresses();
                    $name = $kubIngress->getMetadata('name') ?? $ingress->getName() . self::NAME_PREFIX;
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
