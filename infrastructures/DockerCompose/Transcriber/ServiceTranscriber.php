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

use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Service;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Transport;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\GenerationInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\ExposingInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\TranscriberInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

/**
 * "Exposing transcriber" translating CompiledDeployment's services to the Docker Compose / Traefik model.
 *
 * Unlike Kubernetes there is no Service object: every Compose service is already reachable on the dedicated,
 * internal network by its Compose DNS name. This transcriber therefore only:
 * - registers a Compose network alias equal to the (prefixed) service name on the consuming Compose service,
 *   so the Service name resolves on the network alongside the pod's own name;
 * - for external raw TCP/UDP services, emits a Traefik TCP/UDP router+service bound to the configured
 *   entrypoint (`HostSNI(*)` for TCP). External HTTP(S) services are reached through Traefik via an Ingress;
 *   a bare external HTTP(S) service without an Ingress stays reachable only internally (documented).
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ServiceTranscriber implements ExposingInterface
{
    use CommonTrait;

    public function __construct(
        private readonly string $tcpEntrypoint = 'tcp',
        private readonly string $udpEntrypoint = 'udp',
    ) {
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        GenerationInterface $generation,
        PromiseInterface $promise,
        DefaultsBag $defaultsBag,
        string $namespace,
    ): TranscriberInterface {
        $tcpEntrypoint = $this->tcpEntrypoint;
        $udpEntrypoint = $this->udpEntrypoint;

        $compiledDeployment->foreachService(
            static function (
                Service $service,
                string $prefix
            ) use (
                $generation,
                $promise,
                $tcpEntrypoint,
                $udpEntrypoint,
            ): void {
                try {
                    $prefixer = self::createPrefixer($prefix);
                    $serviceName = $prefixer($service->getName());
                    $result = [];

                    if (
                        !$service->isInternal()
                        && Transport::Https !== $service->getProtocol()
                    ) {
                        $entrypoint = match ($service->getProtocol()) {
                            Transport::Udp => $udpEntrypoint,
                            default => $tcpEntrypoint,
                        };

                        $kind = match ($service->getProtocol()) {
                            Transport::Udp => 'udp',
                            default => 'tcp',
                        };

                        foreach ($service->getPorts() as $listen => $target) {
                            $routerName = self::sanitizeDns($serviceName . '-' . $listen);

                            $routerSpec = [
                                'entryPoints' => [$entrypoint],
                                'service' => $routerName,
                            ];

                            //TCP requires a routing rule; UDP routers have no rule in Traefik v3.
                            if ('tcp' === $kind) {
                                $routerSpec['rule'] = 'HostSNI(`*`)';
                            }

                            $generation->addTraefikRouter($kind, $routerName, $routerSpec);

                            $generation->addTraefikService(
                                $kind,
                                $routerName,
                                [
                                    'loadBalancer' => [
                                        'servers' => [
                                            ['address' => $serviceName . ':' . $target],
                                        ],
                                    ],
                                ],
                            );

                            $result['traefik'][$kind][$routerName] = $routerSpec;
                        }
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
