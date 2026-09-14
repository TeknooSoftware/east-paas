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

use Teknoo\East\Paas\Compilation\CompiledDeployment\Secret;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\AccumulatorInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\TranscriberInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\MountedFile;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

/**
 * "Deployment transcriber" translating CompiledDeployment's secrets (provider `map`, carrying inline values
 * in their options) to Compose `secrets` entries backed by files pushed to the host.
 *
 * Each option key of a secret becomes its own Compose secret
 * `{ <prefixed>-secret-<key>: { file: ./secrets/<prefixed>-secret/<key> } }`, so a secret volume can be
 * mounted exactly like Kubernetes does (one file per key under the declared mount path, see
 * `PodsTranscriberTrait::convertVolumes()`). A `base64:` prefixed value is decoded before being written,
 * mirroring the Kubernetes SecretTranscriber convention. Environment variables read from secrets are handled
 * by the pods transcription (per-container env file), per-ingress TLS by the IngressTranscriber, both from
 * the same decoded values.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class SecretTranscriber implements DeploymentInterface
{
    use CommonTrait;
    use ValuesCollectorTrait;

    private const string NAME_SUFFIX = '-secret';

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        AccumulatorInterface $accumulator,
        PromiseInterface $promise,
        DefaultsBag $defaultsBag,
        string $namespace,
    ): TranscriberInterface {
        $compiledDeployment->foreachSecret(
            static function (Secret $secret, string $prefix) use ($accumulator, $promise): void {
                if (self::MAP_PROVIDER !== $secret->getProvider()) {
                    return;
                }

                $prefixer = self::createPrefixer($prefix);

                try {
                    $baseName = (string) $prefixer($secret->getName() . self::NAME_SUFFIX);
                    $emitted = [];

                    foreach ($secret->getOptions() as $key => $value) {
                        $key = self::sanitizeKey((string) $key);
                        $path = 'secrets/' . $baseName . '/' . $key;

                        $accumulator->addSecret(
                            $baseName . '-' . $key,
                            new MountedFile($path, self::decodeSecretValue($value)),
                        );

                        $emitted[$baseName . '-' . $key] = ['file' => './' . $path];
                    }

                    $promise->success(['secrets' => $emitted]);
                } catch (Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
