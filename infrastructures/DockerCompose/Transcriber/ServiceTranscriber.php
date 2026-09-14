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
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\AccumulatorInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\TranscriberInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

/**
 * "Deployment transcriber" mapping the PaaS services to the Compose services backing their pods.
 *
 * Compose has no Service object: every Compose service is reachable on the dedicated network by its own
 * DNS name (the pod name) on its container ports. Each PaaS service name is therefore declared as a DNS
 * alias of the pod's Compose service (so `http://<service>` resolves inside the stack, on the container
 * port). A public (`!internal`) service gets its `ports:` published on the host (`<listen>:<target>`,
 * `/udp` for UDP), which is only possible for a non-replicated pod (a single container can bind a host
 * port): for a replicated pod the publication is skipped with a warning in the stage result. It runs after
 * the DeploymentTranscriber so the services it mutates already exist in the accumulator.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ServiceTranscriber implements DeploymentInterface
{
    /**
     * @return array<string, int> raw pod name => replicas
     */
    private static function collectReplicas(CompiledDeploymentInterface $compiledDeployment): array
    {
        $replicas = [];

        $compiledDeployment->foreachPod(
            static function (Pod $pod) use (&$replicas): void {
                $replicas[$pod->getName()] = $pod->getReplicas();
            }
        );

        return $replicas;
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        AccumulatorInterface $accumulator,
        PromiseInterface $promise,
        DefaultsBag $defaultsBag,
        string $namespace,
    ): TranscriberInterface {
        $replicas = self::collectReplicas($compiledDeployment);

        $compiledDeployment->foreachService(
            static function (
                Service $service,
                string $prefix
            ) use (
                $accumulator,
                $promise,
                $replicas,
            ): void {
                try {
                    //The DeploymentTranscriber keys each Compose service by the raw pod name (the prefix is
                    //only applied to volume/secret/config references), so the alias and the ports are
                    //declared on that same, unprefixed pod name.
                    $serviceName = $service->getPodName();
                    $accumulator->addNetworkAlias($serviceName, $service->getName());

                    if ($service->isInternal()) {
                        $promise->success(['aliases' => [$serviceName => [$service->getName()]]]);

                        return;
                    }

                    if (($replicas[$serviceName] ?? 1) > 1) {
                        //Not fatal: the service stays reachable inside the stack (alias) and through the
                        //ingresses, only the host port publication is skipped.
                        $warning = "The service `{$service->getName()}` cannot publish host ports for the pod "
                            . "`$serviceName`: it is replicated and a host port can be bound by a single "
                            . 'container on a Docker Compose host, expose it through an ingress instead';
                        $accumulator->addWarning($warning);

                        $promise->success([
                            'aliases' => [$serviceName => [$service->getName()]],
                            'warning' => $warning,
                        ]);

                        return;
                    }

                    $suffix = '';
                    if (Transport::Udp === $service->getProtocol()) {
                        $suffix = '/udp';
                    }

                    $ports = [];
                    foreach ($service->getPorts() as $listen => $target) {
                        $ports[] = $listen . ':' . $target . $suffix;
                    }

                    $accumulator->publishPorts($serviceName, $ports);

                    $promise->success([
                        'aliases' => [$serviceName => [$service->getName()]],
                        'ports' => [$serviceName => $ports],
                    ]);
                } catch (Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
