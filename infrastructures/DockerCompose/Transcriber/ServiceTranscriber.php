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
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\AccumulatorInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\TranscriberInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

/**
 * "Deployment transcriber" publishing the host ports of public services.
 *
 * Every Compose service is reachable on the shared network by its Compose DNS name, so internal services
 * need nothing. A public (`!internal`) service simply gets its `ports:` published on the host (`<listen>:
 * <target>`) on the Compose service backing its pod. It runs after the DeploymentTranscriber so the service
 * it mutates already exists in the accumulator.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ServiceTranscriber implements DeploymentInterface
{
    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        AccumulatorInterface $accumulator,
        PromiseInterface $promise,
        DefaultsBag $defaultsBag,
        string $namespace,
    ): TranscriberInterface {
        $compiledDeployment->foreachService(
            static function (
                Service $service,
                string $prefix
            ) use (
                $accumulator,
                $promise,
            ): void {
                try {
                    if ($service->isInternal()) {
                        $promise->success([]);

                        return;
                    }

                    //The DeploymentTranscriber keys each Compose service by the raw pod name (the prefix is
                    //only applied to volume/secret/config references), so the ports are published on that
                    //same, unprefixed pod name.
                    $serviceName = $service->getPodName();

                    $ports = [];
                    foreach ($service->getPorts() as $listen => $target) {
                        $ports[] = $listen . ':' . $target;
                    }

                    $accumulator->publishPorts($serviceName, $ports);

                    $promise->success(['ports' => [$serviceName => $ports]]);
                } catch (Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
