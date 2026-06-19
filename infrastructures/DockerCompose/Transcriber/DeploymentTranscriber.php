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

use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\Image;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\AccumulatorInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\TranscriberInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

/**
 * "Deployment transcriber" translating CompiledDeployment's pods and their containers into Compose services.
 *
 * A single-container pod becomes one service named after the pod; a multi-container pod becomes an anchor
 * service plus one sidecar per extra container sharing the anchor's network namespace via
 * `network_mode: "service:<anchor>"` (replicating the Kubernetes pod network sharing). Job pods are not
 * processed here: `foreachPod` only yields the top-level pods, jobs are handled by the JobTranscriber.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class DeploymentTranscriber implements DeploymentInterface
{
    use CommonTrait;
    use PodsTranscriberTrait;

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        AccumulatorInterface $accumulator,
        PromiseInterface $promise,
        DefaultsBag $defaultsBag,
        string $namespace,
    ): TranscriberInterface {
        $networkName = $accumulator->getNetworkName();

        $compiledDeployment->foreachPod(
            static function (
                Pod $pod,
                array $images,
                array $volumes,
                string $prefix,
            ) use (
                $accumulator,
                $promise,
                $networkName,
            ): void {
                $prefixer = self::createPrefixer($prefix);

                try {
                    /** @var array<string, array<string, Image>> $images */
                    $services = self::podToServices(
                        pod: $pod,
                        images: $images,
                        prefixer: $prefixer,
                        networkName: $networkName,
                    );

                    foreach ($services as $serviceName => $serviceSpec) {
                        $accumulator->addService($serviceName, $serviceSpec);
                    }

                    $promise->success(['services' => $services]);
                } catch (Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
