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

use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\PersistentVolumeInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\VolumeInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\AccumulatorInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\TranscriberInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

/**
 * "Deployment transcriber" translating CompiledDeployment's persistent volumes to named Compose volumes
 * (`local` driver).
 *
 * Only persistent volumes are declared here, by their bare (prefixed) name. Populated/embedded volumes are
 * handled by the pod transcription (`PodsTranscriberTrait::convertVolumes`), which declares their named
 * volume and an init service that populates it from the volume's OCI image. Secret and map volumes are not
 * declared: they are mounted as Compose `secrets:`/`configs:`. `storageSize`/`allowWriteMany` are advisory
 * on a single local host and are not enforced; `resetOnDeployment` is recorded in the volume's
 * `x-paas-reset` marker so the deploy playbook removes the volume before bringing the stack up.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class VolumeTranscriber implements DeploymentInterface
{
    use CommonTrait;

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        AccumulatorInterface $accumulator,
        PromiseInterface $promise,
        DefaultsBag $defaultsBag,
        string $namespace,
    ): TranscriberInterface {
        $compiledDeployment->foreachVolume(
            static function (
                string $name,
                VolumeInterface $volume,
                string $prefix,
            ) use (
                $accumulator,
                $promise,
            ): void {
                //Only persistent volumes become named Compose volumes here; populated/embedded volumes are
                //declared (and populated via an init service) by the pod transcription, and secret/map
                //volumes become Compose secrets:/configs:.
                if (!$volume instanceof PersistentVolumeInterface) {
                    return;
                }

                $prefixer = self::createPrefixer($prefix);

                try {
                    $volumeName = (string) $prefixer($volume->getName());

                    $spec = [
                        'driver' => 'local',
                    ];

                    if ($volume->isResetOnDeployment()) {
                        $spec['x-paas-reset'] = true;
                    }

                    $accumulator->addVolume($volumeName, $spec);

                    $promise->success(['volumes' => [$volumeName => $spec]]);
                } catch (Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
